<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra người dùng đã đăng nhập và có role 2 hoặc 3 (Quản lý tổ chức hoặc Quản lý sự kiện)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    header('Location: ../login.php');
    exit;
}

// Dữ liệu sẽ được tải qua các lời gọi API AJAX
// Dự phòng: Tải dữ liệu trực tiếp nếu API thất bại
$approvedEvents = [];
$existingPlans = [];

try {
    $pdo = getDBConnection();
    
    // Lấy các sự kiện đã duyệt và trạng thái sự kiện
    // Sử dụng subquery để lấy một kế hoạch cho mỗi sự kiện (ưu tiên kế hoạch đã hoàn thành)
    $sql = "
        SELECT 
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.SoNguoiDuKien,
            dl.NganSach,
            dl.TrangThaiDuyet,
            dl.TrangThaiThanhToan,
            COALESCE(s.TrangThaiThucTe, 'Chưa bắt đầu') as TrangThaiThucTe,
            COALESCE(s.TrangThaiThucTe, 'Chưa bắt đầu') as TrangThaiSuKien,
            (SELECT kht.TrangThai 
             FROM kehoachthuchien kht 
             INNER JOIN sukien s2 ON kht.ID_SuKien = s2.ID_SuKien 
             WHERE s2.ID_DatLich = dl.ID_DatLich 
             ORDER BY CASE WHEN kht.TrangThai = 'Hoàn thành' THEN 0 ELSE 1 END, kht.ID_KeHoach DESC 
             LIMIT 1) as TrangThaiKeHoach,
            (SELECT kht.ID_KeHoach 
             FROM kehoachthuchien kht 
             INNER JOIN sukien s2 ON kht.ID_SuKien = s2.ID_SuKien 
             WHERE s2.ID_DatLich = dl.ID_DatLich 
             ORDER BY CASE WHEN kht.TrangThai = 'Hoàn thành' THEN 0 ELSE 1 END, kht.ID_KeHoach DESC 
             LIMIT 1) as ID_KeHoach,
            COALESCE(dd.TenDiaDiem, 'Chưa xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Chưa xác định') as DiaChi,
            COALESCE(ls.TenLoai, 'Chưa phân loại') as TenLoaiSK,
            COALESCE(kh.HoTen, 'Chưa có thông tin') as TenKhachHang,
            COALESCE(kh.SoDienThoai, 'Chưa có') as SoDienThoai
        FROM datlichsukien dl
        LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.TrangThaiDuyet = 'Đã duyệt' 
            AND dl.TrangThaiThanhToan IN ('Đã đặt cọc', 'Đã thanh toán đủ')
        ORDER BY 
            CASE 
                WHEN COALESCE(s.TrangThaiThucTe, '') = 'Hoàn thành' 
                     OR (SELECT kht.TrangThai 
                         FROM kehoachthuchien kht 
                         INNER JOIN sukien s2 ON kht.ID_SuKien = s2.ID_SuKien 
                         WHERE s2.ID_DatLich = dl.ID_DatLich 
                         ORDER BY CASE WHEN kht.TrangThai = 'Hoàn thành' THEN 0 ELSE 1 END, kht.ID_KeHoach DESC 
                         LIMIT 1) = 'Hoàn thành' THEN 1
                ELSE 0
            END ASC,
            dl.NgayBatDau DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
                $approvedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy các kế hoạch hiện có
    $sql = "
        SELECT 
            kht.ID_KeHoach,
            kht.ID_SuKien,
            kht.TenKeHoach,
            kht.NoiDung,
            kht.NgayBatDau,
            kht.NgayKetThuc,
            kht.TrangThai,
            kht.ID_NhanVien AS ID_NhanVien,
            nv.HoTen AS TenNhanVien,
            s.ID_DatLich,
            dl.TenSuKien
        FROM kehoachthuchien kht
        LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        LEFT JOIN nhanvien nv ON kht.ID_NhanVien = nv.ID_NhanVien
        ORDER BY kht.NgayBatDau ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $existingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update all plan statuses before displaying
    updateAllPlanStatusesInPHP($pdo);
    
} catch (Exception $e) {
    error_log("Error loading fallback data: " . $e->getMessage());
}

// Helper function to update all plan statuses
function updateAllPlanStatusesInPHP($pdo) {
    try {
        // Get all plan IDs
        $sql = "SELECT DISTINCT ID_KeHoach FROM kehoachthuchien";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $planIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Update status for each plan
        foreach ($planIds as $planId) {
            // Get all steps for this plan with their actual status from lichlamviec
            $stepStmt = $pdo->prepare("
                SELECT 
                    ck.ID_ChiTiet,
                    ck.TrangThai as chitiet_status,
                    COUNT(llv.ID_LLV) as total_assignments,
                    SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_assignments,
                    SUM(CASE WHEN llv.TrangThai = 'Đang làm' THEN 1 ELSE 0 END) as inprogress_assignments
                FROM chitietkehoach ck
                LEFT JOIN lichlamviec llv ON ck.ID_ChiTiet = llv.ID_ChiTiet
                WHERE ck.ID_KeHoach = ?
                GROUP BY ck.ID_ChiTiet, ck.TrangThai
            ");
            $stepStmt->execute([$planId]);
            $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($steps)) {
                continue;
            }
            
            $totalSteps = count($steps);
            $completedSteps = 0;
            $inProgressSteps = 0;
            
            foreach ($steps as $step) {
                $totalAssignments = (int)$step['total_assignments'];
                $completedAssignments = (int)$step['completed_assignments'];
                $inProgressAssignments = (int)$step['inprogress_assignments'];
                
                // If step has assignments in lichlamviec, use those to determine status
                if ($totalAssignments > 0) {
                    // Step is completed only if ALL assignments are completed
                    if ($completedAssignments === $totalAssignments && $totalAssignments > 0) {
                        $completedSteps++;
                    } 
                    // Step is in progress if at least one assignment is in progress or completed (but not all)
                    else if ($inProgressAssignments > 0 || $completedAssignments > 0) {
                        $inProgressSteps++;
                    }
                } else {
                    // No assignments in lichlamviec, use chitietkehoach status
                    if ($step['chitiet_status'] === 'Hoàn thành') {
                        $completedSteps++;
                    } else if ($step['chitiet_status'] === 'Đang làm') {
                        $inProgressSteps++;
                    }
                }
            }
            
            // Determine new plan status
            $newPlanStatus = null;
            if ($completedSteps === $totalSteps && $totalSteps > 0) {
                // All steps completed
                $newPlanStatus = 'Hoàn thành';
            } else if ($inProgressSteps > 0 || $completedSteps > 0) {
                // At least one step is in progress or completed
                $newPlanStatus = 'Đang thực hiện';
            } else {
                // All steps are "Chưa làm"
                $newPlanStatus = 'Chưa bắt đầu';
            }
            
            // Update plan status
            if ($newPlanStatus) {
                $updateStmt = $pdo->prepare("
                    UPDATE kehoachthuchien 
                    SET TrangThai = ? 
                    WHERE ID_KeHoach = ?
                ");
                $updateStmt->execute([$newPlanStatus, $planId]);
            }
        }
        
    } catch (Exception $e) {
        error_log("ERROR: updateAllPlanStatusesInPHP - " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lên kế hoạch thực hiện và phân công - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Sửa vị trí nội dung để tránh chồng lên header */
        body {
            padding-top: 0; /* Xóa mọi padding của body */
        }
        
        .container-fluid {
            margin-top: 0;
            padding-top: 20px;
        }
        
        /* Bố cục Trang */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Statistics Cards */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-card p {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        /* Event Planning Cards */
        .planning-card {
            background: white;
            border: 1px solid #e9ecef;
            padding: 1rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .planning-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .planning-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        
        .planning-card:hover::before {
            opacity: 1;
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
        }
        
        .event-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: shimmer 4s ease-in-out infinite;
        }
        
        .event-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }
        
        .event-header h5 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .event-header p {
            margin-bottom: 0;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 2rem;
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            position: relative;
        }
        
        .card-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
        }
        
        .card-body .row {
            margin-bottom: 1.25rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            border: 1px solid rgba(233, 236, 239, 0.5);
            transition: all 0.3s ease;
        }
        
        .card-body .row:hover {
            background: rgba(255,255,255,0.9);
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .card-body .row:last-child {
            margin-bottom: 0;
        }
        
        .card-body small {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-body strong {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.875rem 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 50%, #e085f0 100%);
        }
        
        .btn-outline-info {
            border: 2px solid #17a2b8;
            color: #17a2b8;
            background: transparent;
        }
        
        .btn-outline-info:hover {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border-color: #17a2b8;
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
        }
        
        /* Existing Plans Section */
        .existing-plans-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        /* Enhanced Step Form Styling */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .form-control-lg, .form-select-lg {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control-lg:focus, .form-select-lg:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }
        
        .form-label.fw-bold {
            color: #2c3e50;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .form-label i {
            font-size: 0.8rem;
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            font-weight: 600;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .card.shadow-sm {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
            border: none;
            border-radius: 15px;
        }
        
        .card-header.bg-gradient-primary {
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .card-body.p-4 {
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
        }
        
        .g-3 > * {
            padding: 0.75rem;
        }
        
        /* Placeholder styling */
        .form-control::placeholder, .form-select::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }
        
        /* Enhanced timeline for steps list */
        .timeline-item .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .timeline-item .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .timeline-item .card-body {
            padding: 1.5rem;
        }
        
        .timeline-item h6 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .timeline-item .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }
        
        .timeline-item .badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .btn-group-vertical .btn {
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }
        
        /* Empty state for steps */
        #stepsList .text-muted {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
        }
        
        /* Modal improvements */
        .modal-xl {
            max-width: 95%;
        }
        
        @media (min-width: 1200px) {
            .modal-xl {
                max-width: 1140px;
            }
        }
        
        /* Fix modal positioning and overflow */
        .modal-dialog {
            margin: 0;
            max-width: 95%;
            width: 1200px;
        }
        
        .modal-xl .modal-dialog {
            max-width: 95%;
            width: 1200px;
        }
        
        /* Ensure modal is properly centered */
        .modal.show {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Ensure modal content doesn't overflow */
        .modal-body {
            overflow-x: hidden;
        }
        
        /* Fix button positioning in modal footer */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .modal-footer .btn {
            margin: 0;
        }
        
        /* Ensure form elements don't overflow */
        .form-control, .form-select {
            max-width: 100%;
        }
        
        /* Fix responsive issues */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-xl .modal-dialog {
                max-width: calc(100% - 1rem);
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-footer {
                padding: 1rem;
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .form-control-lg, .form-select-lg {
                font-size: 0.9rem;
                padding: 0.6rem 0.8rem;
            }
            
            .btn-lg {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .card-body.p-4 {
                padding: 1.5rem !important;
            }
            
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .d-flex.gap-2 .btn {
                width: 100%;
            }
        }
        
        /* Force hide bottom existing plans section as requested */
        #existingPlansSection { 
            display: none !important; 
            visibility: hidden !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        
        .existing-plans-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
        }
        
        .existing-plans-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #28a745;
        }
        
        .existing-plans-card h6 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .existing-plans-card .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Timeline for Steps */
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
            width: 3px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .timeline-item .card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .timeline-item .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Event Plans Styling */
        .event-plans-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }
        
        .event-plan-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef !important;
            transition: all 0.3s ease;
        }
        
        .event-plan-item:hover {
            background: #e9ecef;
            border-color: #667eea !important;
            transform: translateY(-1px);
        }
        
        .event-plan-item .text-primary {
            color: #667eea !important;
            font-size: 0.9rem;
        }
        
        .event-plan-item .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Modal Styling - Fixed Z-index */
        .modal {
            z-index: 10000 !important;
        }
        
        .modal-backdrop {
            display: none !important;
        }
        
        .modal.show {
            z-index: 10000 !important;
        }
        
        .modal.show .modal-dialog {
            z-index: 10001 !important;
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal.show .modal-content {
            z-index: 10002 !important;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            position: relative;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            z-index: 1;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 1.4rem;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .modal-body {
            padding: 2.5rem;
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 2.5rem;
            border-radius: 0 0 20px 20px;
            background: #f8f9fa;
        }
        
        /* Ensure modal covers everything */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Fix body scroll when modal is open */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }
        
        /* Ensure sidebar doesn't interfere */
        .sidebar, .admin-header, nav {
            z-index: 1030 !important;
        }
        
        /* Form Improvements for Modal */
        .modal .form-control, .modal .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
            background: white;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .modal .form-control:focus, .modal .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
            background: white;
            transform: translateY(-2px);
        }
        
        .modal .form-label {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Card styling in modal */
        .modal .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .modal .card:hover {
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .modal .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
        }
        
        .modal .card-header h6 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal .card-body {
            padding: 2rem;
            background: white;
        }
        
        /* Button styling in modal */
        .modal .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .modal .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .modal .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .modal .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            background: #5a6268;
        }
        
        /* Loading States */
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container-fluid {
                padding-top: 10px;
            }
           
            .page-title {
                font-size: 2rem;
            }
           
            .stats-card h3 {
                font-size: 2rem;
            }
           
            .planning-card {
                margin-bottom: 1.5rem;
            }
           
            .event-header {
                padding: 1rem;
            }
           
            .card-body {
                padding: 1rem;
            }
           
            .modal-dialog {
                margin: 1rem;
                max-width: 95% !important;
            }
           
            .modal-body {
                padding: 1rem;
            }
        }
        
        /* Ensure menu/sidebar doesn't overlap modal */
        .sidebar, .admin-header, nav {
            z-index: 1030 !important;
            pointer-events: auto !important;
        }
        
        /* Keep body scroll when modal open */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }
        
        /* Ensure modal is always on top */
        .modal.show {
            z-index: 10000 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Fix modal positioning to center properly */
        .modal.show .modal-dialog {
            position: fixed !important;
            z-index: 10001 !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            max-width: 95vw;
            max-height: 90vh;
            width: 1200px;
        }
        
        /* Ensure modal doesn't overlap with sidebar */
        @media (min-width: 769px) {
            .modal.show .modal-dialog {
                left: calc(50% + 125px) !important; /* Offset for sidebar width */
                max-width: calc(95vw - 250px);
                width: 1000px;
            }
            
            /* Ensure modal content fits properly */
            .modal-content {
                max-width: 100%;
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .modal.show .modal-dialog {
                left: 50% !important;
                max-width: 95vw;
                width: 95vw;
            }
        }
        
        /* Additional responsive fixes */
        @media (min-width: 1200px) {
            .modal.show .modal-dialog {
                left: calc(50% + 125px) !important;
                max-width: calc(100vw - 300px);
                width: 1200px;
            }
        }
        
        @media (min-width: 1400px) {
            .modal.show .modal-dialog {
                width: 1400px;
            }
        }
        
        /* Alert Improvements */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1.25rem 1.5rem;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        /* Staff Selection Checkbox List */
        .staff-selection-container {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .staff-selection-container:hover {
            border-color: #667eea;
        }
        
        .staff-checkbox-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .staff-checkbox-item:hover {
            background: #f0f4ff;
            border-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }
        
        .staff-checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .staff-checkbox-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .staff-checkbox-item .staff-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .staff-checkbox-item .staff-role {
            color: #6c757d;
            font-size: 0.85rem;
            font-style: italic;
        }
        
        .staff-checkbox-item input[type="checkbox"]:checked + label .staff-name {
            color: #667eea;
        }
        
        .staff-checkbox-item:has(input[type="checkbox"]:checked) {
            background: #e8f0fe;
            border-color: #667eea;
        }
    </style>
    
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt"></i>
                        Lên kế hoạch thực hiện và phân công
                    </h1>
                    <p class="page-subtitle">Tạo và quản lý kế hoạch thực hiện cho các sự kiện đã được duyệt</p>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-primary" id="approvedEventsCount">-</h3>
                            <p>Sự kiện đã duyệt</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-success" id="totalPlansCount">-</h3>
                            <p>Kế hoạch đã tạo</p>
                            </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-warning" id="inProgressPlansCount">-</h3>
                            <p>Đang thực hiện</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-info" id="completedPlansCount">-</h3>
                            <p>Hoàn thành</p>
                            </div>
                        </div>
                    </div>

                <!-- Filter Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Tìm kiếm</label>
                                        <input type="text" id="eventSearchInput" class="form-control form-control-sm" placeholder="Tên sự kiện, địa điểm, khách hàng...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Loại sự kiện</label>
                                        <select id="eventTypeFilter" class="form-select form-select-sm">
                                            <option value="">Tất cả</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Từ ngày</label>
                                        <input type="date" id="eventDateFrom" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Đến ngày</label>
                                        <input type="date" id="eventDateTo" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetEventFilters()" title="Xóa bộ lọc">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events List -->
                <div class="row" id="eventsList">
                    <div class="col-12">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2">Đang tải danh sách sự kiện...</p>
                        </div>
                    </div>
                </div>

                <!-- Existing Plans -->
        <div class="mt-5" id="existingPlansSection" style="display: none;">
                    <h4 class="mb-3">
                        <i class="fas fa-list-check"></i>
                        Kế hoạch đã tạo
                    </h4>
                    <div class="row" id="existingPlansList">
                        <!-- Plans will be loaded here via JavaScript -->
                        </div>
                                </div>
                            </div>
                            </div>
                        </div>

    <!-- Create Plan Modal -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Tạo kế hoạch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                <div class="modal-body">
                    <form id="createPlanForm">
                        <input type="hidden" id="eventId" name="eventId">
                                        
                                        <div class="mb-3">
                            <label for="planName" class="form-label">Tên kế hoạch</label>
                            <input type="text" class="form-control" id="planName" name="planName" required>
                                        </div>
                                        
                                        <div class="mb-3">
                            <label for="planContent" class="form-label">Nội dung kế hoạch</label>
                            <textarea class="form-control" id="planContent" name="planContent" rows="4" required></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="startDate" name="startDate" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="startTime" class="form-label">Giờ bắt đầu</label>
                                <input type="time" class="form-control" id="startTime" name="startTime" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="endDate" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="endDate" name="endDate" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="endTime" class="form-label">Giờ kết thúc</label>
                                <input type="time" class="form-control" id="endTime" name="endTime" required>
                                            </div>
                                        </div>
                        
                                                <div class="mb-3">
                            <label for="assignedStaff" class="form-label">Nhân viên phụ trách</label>
                            <select class="form-select" id="assignedStaff" name="assignedStaff">
                                <option value="">Chọn nhân viên</option>
                            </select>
                                                </div>
                    </form>
                                            </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="savePlan()">Tạo kế hoạch</button>
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
                        <i class="fas fa-cogs"></i> Quản lý bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stepEventId" name="eventId">
                    
                    <div class="row g-4">
                        <!-- Left Side: Steps List -->
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-gradient-info text-white">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-list-check me-2"></i> 
                                        Danh sách các bước
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div id="stepsList" class="steps-timeline">
                                        <!-- Steps will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side: Add Step Form -->
                        <div class="col-md-6" id="addStepFormContainer">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-gradient-primary text-white">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-plus-circle me-2"></i> 
                                        Thêm bước thực hiện mới
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <form id="addStepForm">
                                        <div class="mb-3">
                                            <label for="stepName" class="form-label fw-bold">
                                                <i class="fas fa-tag text-primary me-1"></i>Tên bước
                                            </label>
                                            <input type="text" class="form-control form-control-lg" id="stepName" name="stepName" 
                                                   placeholder="Nhập tên bước thực hiện" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-users text-primary me-1"></i>Nhân viên phụ trách
                                            </label>
                                            <div class="staff-selection-container border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8f9fa;">
                                                <div id="stepStaffCheckboxes" class="staff-checkbox-list">
                                                    <div class="text-center text-muted py-3">
                                                        <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách nhân viên...
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepDescription" class="form-label fw-bold">
                                                <i class="fas fa-file-text text-primary me-1"></i>Mô tả chi tiết
                                            </label>
                                            <textarea class="form-control" id="stepDescription" name="stepDescription" 
                                                      rows="3" placeholder="Mô tả chi tiết về bước thực hiện này..."></textarea>
                                        </div>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="stepStartDate" class="form-label fw-bold">
                                                    <i class="fas fa-calendar text-primary me-1"></i>Ngày bắt đầu
                                                </label>
                                                <input type="date" class="form-control" id="stepStartDate" name="stepStartDate" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="stepStartTime" class="form-label fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>Giờ bắt đầu
                                                </label>
                                                <input type="time" class="form-control" id="stepStartTime" name="stepStartTime" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="stepEndDate" class="form-label fw-bold">
                                                    <i class="fas fa-calendar text-primary me-1"></i>Ngày kết thúc
                                                </label>
                                                <input type="date" class="form-control" id="stepEndDate" name="stepEndDate" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="stepEndTime" class="form-label fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>Giờ kết thúc
                                                </label>
                                                <input type="time" class="form-control" id="stepEndTime" name="stepEndTime" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="stepNote" class="form-label fw-bold">
                                                <i class="fas fa-sticky-note text-primary me-1"></i>Ghi chú bổ sung
                                            </label>
                                            <textarea class="form-control" id="stepNote" name="note" rows="2" 
                                                      placeholder="Thêm ghi chú hoặc lưu ý đặc biệt..."></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary btn-lg" onclick="addStep()">
                                                <i class="fas fa-plus me-2"></i>Thêm bước thực hiện
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="resetStepForm()">
                                                <i class="fas fa-undo me-1"></i>Làm mới form
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

    <!-- Edit Plan Modal -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-pen-to-square"></i> Sửa kế hoạch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlanForm">
                        <input type="hidden" id="editPlanId" name="planId">
                        <div class="mb-3">
                            <label for="editAssignedStaff" class="form-label">Nhân viên phụ trách</label>
                            <select class="form-select" id="editAssignedStaff" name="assignedStaff">
                                <option value="">Chọn nhân viên</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPlanName" class="form-label">Tên kế hoạch</label>
                            <input type="text" class="form-control" id="editPlanName" name="planName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPlanContent" class="form-label">Nội dung kế hoạch</label>
                            <textarea class="form-control" id="editPlanContent" name="planContent" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="editStartDate" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="editStartDate" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editStartTime" class="form-label">Giờ bắt đầu</label>
                                <input type="time" class="form-control" id="editStartTime" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editEndDate" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="editEndDate" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editEndTime" class="form-label">Giờ kết thúc</label>
                                <input type="time" class="form-control" id="editEndTime" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="editStatus" class="form-label">Trạng thái</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                <option value="Đang thực hiện">Đang thực hiện</option>
                                <option value="Hoàn thành">Hoàn thành</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditPlan()">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Step Modal -->
    <div class="modal fade" id="editStepModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Chỉnh sửa bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStepForm">
                        <input type="hidden" id="editStepId" name="stepId">
                        
                        <div class="mb-3">
                            <label for="editStepName" class="form-label">Tên bước *</label>
                            <input type="text" class="form-control" id="editStepName" name="stepName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStepDescription" class="form-label">Mô tả chi tiết</label>
                            <textarea class="form-control" id="editStepDescription" name="stepDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStepStartDate" class="form-label">Ngày bắt đầu *</label>
                                <input type="date" class="form-control" id="editStepStartDate" name="stepStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editStepStartTime" class="form-label">Giờ bắt đầu *</label>
                                <input type="time" class="form-control" id="editStepStartTime" name="stepStartTime" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStepEndDate" class="form-label">Ngày kết thúc *</label>
                                <input type="date" class="form-control" id="editStepEndDate" name="stepEndDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editStepEndTime" class="form-label">Giờ kết thúc *</label>
                                <input type="time" class="form-control" id="editStepEndTime" name="stepEndTime" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nhân viên phụ trách</label>
                            <div class="staff-selection-container border rounded p-3" style="max-height: 200px; overflow-y: auto; background: #f8f9fa;">
                                <div id="editStepStaffCheckboxes" class="staff-checkbox-list">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách nhân viên...
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStepNote" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="editStepNote" name="stepNote" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditStep()">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Biến toàn cục để lưu dữ liệu
        let approvedEvents = <?= json_encode($approvedEvents) ?>;
        let existingPlans = <?= json_encode($existingPlans) ?>;
        
        // Ẩn overlay loading khi trang đã tải xong
        document.addEventListener('DOMContentLoaded', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
            
            // Tải dữ liệu ban đầu
            loadPageData();
            
            // Add event listeners for filters
            const eventSearch = document.getElementById('eventSearchInput');
            const eventType = document.getElementById('eventTypeFilter');
            const eventDateFrom = document.getElementById('eventDateFrom');
            const eventDateTo = document.getElementById('eventDateTo');
            
            if (eventSearch) eventSearch.addEventListener('input', filterEvents);
            if (eventType) eventType.addEventListener('change', filterEvents);
            if (eventDateFrom) eventDateFrom.addEventListener('change', filterEvents);
            if (eventDateTo) eventDateTo.addEventListener('change', filterEvents);
        });
        
        // Cũng ẩn overlay loading trên window load như dự phòng
        window.addEventListener('load', function() {
                const pageLoading = document.getElementById('pageLoading');
                if (pageLoading) {
                    pageLoading.style.display = 'none';
                }
        });
        
        // Tự động làm mới các bước mỗi 30 giây để đồng bộ với cập nhật nhân viên
        // Auto-refresh steps every 10 seconds to update progress bars when staff updates progress
        setInterval(function() {
            const currentEventId = document.getElementById('stepEventId')?.value;
            if (currentEventId) {
                // Check if event is completed
                const event = approvedEvents.find(e => e.ID_DatLich == currentEventId);
                const isReadOnly = event && event.TrangThaiSuKien === 'Hoàn thành';
                console.log('Auto-refreshing steps for event:', currentEventId, 'isReadOnly:', isReadOnly);
                loadSteps(currentEventId, isReadOnly);
            }
        }, 10000);
        
        // Load page data - using PHP data directly for now
        function loadPageData() {
            console.log('Loading page data from PHP variables');
            console.log('Approved events:', approvedEvents);
            console.log('Existing plans:', existingPlans);
            
            // Data is already loaded from PHP, just display it
            displayEvents();
            displayPlans();
            updateStatistics();
            
            // Populate filter dropdowns
            populateEventFilters();
            
            // Also load plans from API to ensure we have latest data
            loadPlansFromAPI();
        }
        
        // Populate filter dropdowns with unique values
        function populateEventFilters() {
            // First, get unique event types from approved events (for quick display)
            const eventTypesFromEvents = new Set();
            approvedEvents.forEach(event => {
                if (event.TenLoaiSK && event.TenLoaiSK !== 'Chưa phân loại') {
                    eventTypesFromEvents.add(event.TenLoaiSK);
                }
            });
            
            // Populate event type filter with event types from approved events first
            const eventTypeFilter = document.getElementById('eventTypeFilter');
            if (eventTypeFilter) {
                // Clear existing options except "Tất cả"
                eventTypeFilter.innerHTML = '<option value="">Tất cả</option>';
                
                // Add event types from approved events
                Array.from(eventTypesFromEvents).sort().forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.toLowerCase();
                    option.textContent = type;
                    eventTypeFilter.appendChild(option);
                });
            }
            
            // Then, fetch all event types from database to ensure we have everything
            fetch('../src/controllers/event-types.php?action=get_all_public', {
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.event_types) {
                    const eventTypeFilter = document.getElementById('eventTypeFilter');
                    if (eventTypeFilter) {
                        // Get existing values to avoid duplicates
                        const existingValues = new Set();
                        Array.from(eventTypeFilter.options).forEach(opt => {
                            if (opt.value) existingValues.add(opt.value.toLowerCase());
                        });
                        
                        // Add all event types from database
                        data.event_types.forEach(eventType => {
                            const typeName = eventType.TenLoai || eventType.ten_loai;
                            if (typeName && typeName !== 'Chưa phân loại' && !existingValues.has(typeName.toLowerCase())) {
                                const option = document.createElement('option');
                                option.value = typeName.toLowerCase();
                                option.textContent = typeName;
                                eventTypeFilter.appendChild(option);
                            }
                        });
                        
                        // Sort options alphabetically (keep "Tất cả" at top)
                        const options = Array.from(eventTypeFilter.options);
                        const allOption = options[0]; // "Tất cả"
                        const otherOptions = options.slice(1).sort((a, b) => {
                            return a.textContent.localeCompare(b.textContent, 'vi');
                        });
                        eventTypeFilter.innerHTML = '';
                        eventTypeFilter.appendChild(allOption);
                        otherOptions.forEach(opt => eventTypeFilter.appendChild(opt));
                    }
                }
            })
            .catch(error => {
                console.error('Error loading all event types:', error);
                // Continue with event types from approved events only
            });
        }
        
        // Filter events based on search criteria
        function filterEvents() {
            const searchTerm = document.getElementById('eventSearchInput')?.value.toLowerCase() || '';
            const eventType = document.getElementById('eventTypeFilter')?.value || '';
            const dateFrom = document.getElementById('eventDateFrom')?.value || '';
            const dateTo = document.getElementById('eventDateTo')?.value || '';
            
            const eventCards = document.querySelectorAll('.event-card');
            let visibleCount = 0;
            
            eventCards.forEach(card => {
                const eventName = card.getAttribute('data-event-name') || '';
                const eventTypeValue = card.getAttribute('data-event-type') || '';
                const locationValue = card.getAttribute('data-location') || '';
                const customer = card.getAttribute('data-customer') || '';
                const startDate = card.getAttribute('data-start-date') || '';
                const endDate = card.getAttribute('data-end-date') || '';
                
                // Search filter
                const matchesSearch = !searchTerm || 
                    eventName.includes(searchTerm) || 
                    locationValue.includes(searchTerm) || 
                    customer.includes(searchTerm);
                
                // Event type filter
                const matchesType = !eventType || eventTypeValue === eventType;
                
                // Date filter - check if event overlaps with date range
                let matchesDate = true;
                if (dateFrom || dateTo) {
                    if (dateFrom && dateTo) {
                        // Event must overlap with the date range
                        matchesDate = (startDate <= dateTo && endDate >= dateFrom);
                    } else if (dateFrom) {
                        matchesDate = endDate >= dateFrom;
                    } else if (dateTo) {
                        matchesDate = startDate <= dateTo;
                    }
                }
                
                if (matchesSearch && matchesType && matchesDate) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show message if no results
            const eventsList = document.getElementById('eventsList');
            let noResultsMessage = eventsList.querySelector('.no-results-message');
            if (visibleCount === 0 && eventCards.length > 0) {
                if (!noResultsMessage) {
                    noResultsMessage = document.createElement('div');
                    noResultsMessage.className = 'col-12 no-results-message';
                    noResultsMessage.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>Không tìm thấy sự kiện nào</h4>
                            <p>Không có sự kiện nào phù hợp với bộ lọc của bạn.</p>
                        </div>
                    `;
                    eventsList.appendChild(noResultsMessage);
                }
                noResultsMessage.style.display = '';
            } else if (noResultsMessage) {
                noResultsMessage.style.display = 'none';
            }
        }
        
        // Reset all filters
        function resetEventFilters() {
            document.getElementById('eventSearchInput').value = '';
            document.getElementById('eventTypeFilter').value = '';
            document.getElementById('eventDateFrom').value = '';
            document.getElementById('eventDateTo').value = '';
            filterEvents();
        }
        
        // Load plans from API
        function loadPlansFromAPI() {
            fetch('../src/controllers/event-planning.php?action=get_plans', {
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success && data.plans) {
                    console.log('Loaded plans from API:', data.plans);
                    existingPlans = data.plans;
                    displayPlans();
                    updateStatistics();
                    
                    // Reload plans for each event
                    approvedEvents.forEach(event => {
                        loadEventPlans(event.ID_DatLich);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading plans from API:', error);
            });
        }
        
        // Display events in the UI
        function displayEvents() {
            const eventsList = document.getElementById('eventsList');
            
            if (approvedEvents.length === 0) {
                eventsList.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>Chưa có sự kiện nào được duyệt</h4>
                            <p>Vui lòng duyệt sự kiện trước khi tạo kế hoạch thực hiện.</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Sắp xếp sự kiện: đã hoàn thành xuống dưới, còn lại lên trên
            // Loại bỏ các sự kiện trùng lặp (do JOIN với kế hoạch)
            const uniqueEvents = [];
            const seenEventIds = new Set();
            
            approvedEvents.forEach(event => {
                if (!seenEventIds.has(event.ID_DatLich)) {
                    seenEventIds.add(event.ID_DatLich);
                    uniqueEvents.push(event);
                } else {
                    // Nếu sự kiện đã tồn tại, cập nhật nếu có kế hoạch hoàn thành
                    const existingIndex = uniqueEvents.findIndex(e => e.ID_DatLich === event.ID_DatLich);
                    if (existingIndex !== -1) {
                        const existing = uniqueEvents[existingIndex];
                        // Ưu tiên kế hoạch đã hoàn thành
                        if (event.TrangThaiKeHoach === 'Hoàn thành' && existing.TrangThaiKeHoach !== 'Hoàn thành') {
                            uniqueEvents[existingIndex] = event;
                        }
                    }
                }
            });
            
            const sortedEvents = uniqueEvents.sort((a, b) => {
                const aCompleted = (a.TrangThaiThucTe === 'Hoàn thành') || (a.TrangThaiKeHoach === 'Hoàn thành');
                const bCompleted = (b.TrangThaiThucTe === 'Hoàn thành') || (b.TrangThaiKeHoach === 'Hoàn thành');
                
                // Nếu cả hai đều hoàn thành hoặc cả hai đều chưa hoàn thành, sắp xếp theo ngày bắt đầu (mới nhất lên trên)
                if (aCompleted === bCompleted) {
                    const dateA = new Date(a.NgayBatDau);
                    const dateB = new Date(b.NgayBatDau);
                    return dateB - dateA; // Mới nhất lên trên
                }
                
                // Sự kiện chưa hoàn thành lên trên (trả về -1), đã hoàn thành xuống dưới (trả về 1)
                return aCompleted ? 1 : -1;
            });
            
            let html = '';
            sortedEvents.forEach(event => {
                // Parse datetime to get both date and time
                const startDateTime = new Date(event.NgayBatDau);
                const endDateTime = new Date(event.NgayKetThuc);
                
                const startDate = startDateTime.toLocaleDateString('vi-VN');
                const startTime = startDateTime.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                const endDate = endDateTime.toLocaleDateString('vi-VN');
                const endTime = endDateTime.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                
                // Format date for filtering (YYYY-MM-DD)
                const startDateFilter = startDateTime.toISOString().split('T')[0];
                const endDateFilter = endDateTime.toISOString().split('T')[0];
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-4 event-card" 
                         data-event-id="${event.ID_DatLich}"
                         data-event-name="${escapeHtml(event.TenSuKien).toLowerCase()}"
                         data-event-type="${escapeHtml(event.TenLoaiSK).toLowerCase()}"
                         data-location="${escapeHtml(event.TenDiaDiem).toLowerCase()}"
                         data-customer="${escapeHtml(event.TenKhachHang).toLowerCase()}"
                         data-start-date="${startDateFilter}"
                         data-end-date="${endDateFilter}">
                        <div class="planning-card">
                            <div class="event-header">
                                <h5 class="mb-1">${escapeHtml(event.TenSuKien)}</h5>
                                <p class="mb-1">
                                    <i class="fas fa-calendar"></i>
                                    ${startDate} - ${endDate}
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock"></i>
                                    ${startTime} - ${endTime}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Địa điểm:</small><br>
                                        <strong>${escapeHtml(event.TenDiaDiem)}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Loại sự kiện:</small><br>
                                        <strong>${escapeHtml(event.TenLoaiSK)}</strong>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Số người:</small><br>
                                        <strong>${Number(event.SoNguoiDuKien).toLocaleString()}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Ngân sách:</small><br>
                                        <strong>${Number(event.NganSach).toLocaleString()} VNĐ</strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Khách hàng:</small><br>
                                    <strong>${escapeHtml(event.TenKhachHang)}</strong><br>
                                    <small class="text-muted">${escapeHtml(event.SoDienThoai)}</small>
                                </div>
                                
                                <!-- Existing Plans for this Event -->
                                <div class="mb-3" id="plans-${event.ID_DatLich}">
                                    <small class="text-muted">Kế hoạch đã tạo:</small>
                                    <div class="event-plans-list" id="event-plans-${event.ID_DatLich}">
                                        <!-- Plans will be loaded here -->
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <!-- Buttons will be updated by loadEventPlans() -->
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            eventsList.innerHTML = html;
            
            // Load plans for each event (sử dụng sortedEvents để giữ thứ tự đã sắp xếp)
            sortedEvents.forEach(event => {
                loadEventPlans(event.ID_DatLich);
            });
        }
        
        // Load plans for a specific event
        function loadEventPlans(eventId) {
            const eventPlansContainer = document.getElementById(`event-plans-${eventId}`);
            if (!eventPlansContainer) return;
            
            console.log('Loading plans for event:', eventId);
            
            // Load plans for this specific event from backend
            fetch(`../src/controllers/event-planning.php?action=get_plans&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Plans data for event', eventId, ':', data);
                
                if (!data.success || !data.plans || data.plans.length === 0) {
                    eventPlansContainer.innerHTML = '<small class="text-muted">Chưa có kế hoạch</small>';
                    // Update event card buttons based on whether plan exists
                    updateEventCardButtons(eventId, false, false);
                    return;
                }
                
                // Only show the first plan (each event should have only one plan)
                const plan = data.plans[0];
                const allStepsCompleted = plan.TrangThai === 'Hoàn thành';
                
                // Handle both date and datetime formats
                let startDate, endDate;
                try {
                    if (plan.NgayBatDau.includes(' ')) {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    } else {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    }
                    
                    if (plan.NgayKetThuc.includes(' ')) {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    } else {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    }
                } catch (e) {
                    startDate = plan.NgayBatDau || 'N/A';
                    endDate = plan.NgayKetThuc || 'N/A';
                }
                
                const statusClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                  plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                let html = `
                    <div class="event-plan-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="text-primary fs-6">${escapeHtml(plan.TenKeHoach)}</strong>
                                    <span class="badge bg-${statusClass} px-2 py-1">${escapeHtml(plan.TrangThai)}</span>
                                </div>
                                <div class="text-muted mb-2 small">
                                    <i class="fas fa-calendar text-success me-1"></i>
                                    ${startDate} - ${endDate}
                                </div>
                                ${plan.NoiDung ? `<div class="text-muted mb-2 small"><i class="fas fa-file-lines text-info me-1"></i> ${escapeHtml(plan.NoiDung)}</div>` : ''}
                                <div class="text-muted small">
                                    <i class="fas fa-user text-warning me-1"></i> 
                                    ${plan.TenNhanVien ? `
                                        <span class="fw-bold text-dark">${escapeHtml(plan.TenNhanVien)}</span>
                                        ${plan.ChucVu ? `<small class="text-muted ms-1">- ${escapeHtml(plan.ChucVu)}</small>` : ''}
                                    ` : '<span class="text-muted">Chưa phân công</span>'}
                                </div>
                            </div>
                            ${allStepsCompleted ? `
                            <div class="ms-3 d-flex flex-column align-items-end">
                                <span class="badge bg-success mb-2">Đã hoàn thành</span>
                            </div>
                            ` : `
                            <div class="ms-3 d-flex flex-column align-items-end">
                                <button class="btn btn-sm btn-outline-primary mb-2 px-3"
                                        onclick="editPlan(${plan.ID_KeHoach || ''})">
                                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                </button>
                            </div>
                            `}
                        </div>
                    </div>`;
            
                eventPlansContainer.innerHTML = html;
                
                // Update event card buttons based on plan status
                updateEventCardButtons(eventId, true, allStepsCompleted);
            })
            .catch(error => {
                console.error('Error loading event plans:', error);
                eventPlansContainer.innerHTML = '<small class="text-danger">Lỗi khi tải kế hoạch</small>';
                updateEventCardButtons(eventId, false, false);
            });
        }
        
        // Update event card buttons based on plan existence and completion status
        function updateEventCardButtons(eventId, hasPlan, allStepsCompleted) {
            const eventCard = document.querySelector(`.event-card[data-event-id="${eventId}"]`);
            if (!eventCard) return;
            
            const buttonContainer = eventCard.querySelector('.d-grid.gap-2');
            if (!buttonContainer) return;
            
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (!event) return;
            
            // If event is completed, show read-only view
            if (event.TrangThaiSuKien === 'Hoàn thành') {
                buttonContainer.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> Sự kiện đã hoàn thành - Chỉ được xem
                    </div>
                    <button class="btn btn-outline-info" onclick="manageSteps(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-eye"></i> Xem kế hoạch
                    </button>
                `;
                return;
            }
            
            // If plan exists and all steps are completed, show read-only view
            if (hasPlan && allStepsCompleted) {
                buttonContainer.innerHTML = `
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Tất cả các bước đã hoàn thành - Chỉ được xem
                    </div>
                    <button class="btn btn-outline-info" onclick="manageSteps(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-eye"></i> Xem kế hoạch
                    </button>
                `;
                return;
            }
            
            // If plan exists but not all steps completed, show manage button only
            if (hasPlan && !allStepsCompleted) {
                buttonContainer.innerHTML = `
                    <button class="btn btn-outline-info" onclick="manageSteps(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-cogs"></i> Quản lý bước
                    </button>
                `;
                return;
            }
            
            // If no plan exists, show create plan button
            if (!hasPlan) {
                buttonContainer.innerHTML = `
                    <button class="btn btn-primary" onclick="createPlan(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-plus"></i> Tạo kế hoạch
                    </button>
                `;
            }
        }
        
        // Display plans in the UI
        function displayPlans() {
            const existingPlansSection = document.getElementById('existingPlansSection');
            const existingPlansList = document.getElementById('existingPlansList');
            
            // Always show section if there are plans
            if (existingPlans.length > 0) {
                existingPlansSection.style.display = 'block';
            } else {
                existingPlansSection.style.display = 'none';
                return;
            }
            
            let html = '';
            existingPlans.forEach(plan => {
                // Handle both date and datetime formats
                let startDate, endDate;
                try {
                    if (plan.NgayBatDau.includes(' ')) {
                        // It's a datetime string
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    } else {
                        // It's a date string
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    }
                    
                    if (plan.NgayKetThuc.includes(' ')) {
                        // It's a datetime string
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    } else {
                        // It's a date string
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    }
                } catch (e) {
                    startDate = plan.NgayBatDau || 'N/A';
                    endDate = plan.NgayKetThuc || 'N/A';
                }
                
                const statusClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                  plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="existing-plans-card">
                            <h6>${escapeHtml(plan.ten_kehoach)}</h6>
                            <p class="card-text">${escapeHtml(plan.NoiDung)}</p>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                ${startDate} - ${endDate}
                            </small>
                            <div class="mt-2">
                                <span class="badge bg-${statusClass}">
                                    ${escapeHtml(plan.TrangThai)}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            existingPlansList.innerHTML = html;
        }

        // Open edit plan modal with data
        function openEditPlanModal(planData) {
            try {
                // Parse when called from inline html (escaped JSON)
                if (typeof planData === 'string') {
                    planData = JSON.parse(planData);
                }
            } catch (e) {}

            const modalEl = document.getElementById('editPlanModal');
            document.getElementById('editPlanId').value = planData.id || planData.ID_KeHoach || '';
            document.getElementById('editPlanName').value = planData.name || planData.ten_kehoach || '';
            document.getElementById('editPlanContent').value = planData.content || planData.NoiDung || '';

            // Split datetime into date and time - handle multiple formats
            let startDate = '';
            let startTime = '08:00';
            let endDate = '';
            let endTime = '17:00';
            
            const start = (planData.start || planData.NgayBatDau || '').toString().trim();
            const end = (planData.end || planData.NgayKetThuc || '').toString().trim();
            
            if (start) {
                if (start.includes(' ')) {
                    // Format: "YYYY-MM-DD HH:MM:SS"
                    const parts = start.split(' ');
                    startDate = parts[0];
                    startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Take only HH:MM
                } else if (start.includes('T')) {
                    // Format: "YYYY-MM-DDTHH:MM:SS"
                    const parts = start.split('T');
                    startDate = parts[0];
                    startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Take only HH:MM
                } else {
                    // Format: "YYYY-MM-DD"
                    startDate = start;
                    startTime = '08:00';
                }
            }
            
            if (end) {
                if (end.includes(' ')) {
                    // Format: "YYYY-MM-DD HH:MM:SS"
                    const parts = end.split(' ');
                    endDate = parts[0];
                    endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Take only HH:MM
                } else if (end.includes('T')) {
                    // Format: "YYYY-MM-DDTHH:MM:SS"
                    const parts = end.split('T');
                    endDate = parts[0];
                    endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Take only HH:MM
                } else {
                    // Format: "YYYY-MM-DD"
                    endDate = end;
                    endTime = '17:00';
                }
            }
            
            document.getElementById('editStartDate').value = startDate;
            document.getElementById('editStartTime').value = startTime;
            document.getElementById('editEndDate').value = endDate;
            document.getElementById('editEndTime').value = endTime;
            document.getElementById('editStatus').value = planData.status || planData.TrangThai || 'Chưa bắt đầu';

            // Load staff options into edit select, then preselect if available
            loadStaffOptions().then(() => {
                const staffSelect = document.getElementById('editAssignedStaff');
                if (planData.ID_NhanVien || planData.ID_NhanVien) {
                    staffSelect.value = planData.ID_NhanVien || planData.ID_NhanVien;
                }
            }).catch(() => {});

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        function editPlan(planId) {
            // Check if plan's event is completed
            const plan = existingPlans.find(p => (p.ID_KeHoach == planId || p.id == planId));
            if (plan) {
                const event = approvedEvents.find(e => e.ID_DatLich == plan.ID_DatLich);
                if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                    alert('Sự kiện đã hoàn thành, không thể chỉnh sửa kế hoạch');
                    return;
                }
            }
            
            console.log('Editing plan:', planId);
            
            // Fetch plan data from database
            fetch(`../src/controllers/event-planning.php?action=get_plan&plan_id=${planId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.plan) {
                        const plan = data.plan;
                        console.log('Plan data loaded:', plan);
                        
                        // Fill edit plan modal
                        document.getElementById('editPlanId').value = plan.ID_KeHoach || '';
                        document.getElementById('editPlanName').value = plan.TenKeHoach || '';
                        document.getElementById('editPlanContent').value = plan.NoiDung || '';
                        
                        // Split datetime - handle multiple formats
                        let startDate = '';
                        let startTime = '08:00';
                        let endDate = '';
                        let endTime = '17:00';
                        
                        if (plan.NgayBatDau) {
                            // Handle formats: "YYYY-MM-DD HH:MM:SS", "YYYY-MM-DDTHH:MM:SS", "YYYY-MM-DD"
                            const startDateTime = plan.NgayBatDau.toString().trim();
                            if (startDateTime.includes(' ')) {
                                // Format: "YYYY-MM-DD HH:MM:SS"
                                const parts = startDateTime.split(' ');
                                startDate = parts[0];
                                startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Take only HH:MM
                            } else if (startDateTime.includes('T')) {
                                // Format: "YYYY-MM-DDTHH:MM:SS"
                                const parts = startDateTime.split('T');
                                startDate = parts[0];
                                startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Take only HH:MM
                            } else {
                                // Format: "YYYY-MM-DD"
                                startDate = startDateTime;
                                startTime = '08:00';
                            }
                        }
                        
                        if (plan.NgayKetThuc) {
                            // Handle formats: "YYYY-MM-DD HH:MM:SS", "YYYY-MM-DDTHH:MM:SS", "YYYY-MM-DD"
                            const endDateTime = plan.NgayKetThuc.toString().trim();
                            if (endDateTime.includes(' ')) {
                                // Format: "YYYY-MM-DD HH:MM:SS"
                                const parts = endDateTime.split(' ');
                                endDate = parts[0];
                                endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Take only HH:MM
                            } else if (endDateTime.includes('T')) {
                                // Format: "YYYY-MM-DDTHH:MM:SS"
                                const parts = endDateTime.split('T');
                                endDate = parts[0];
                                endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Take only HH:MM
                            } else {
                                // Format: "YYYY-MM-DD"
                                endDate = endDateTime;
                                endTime = '17:00';
                            }
                        }
                        
                        document.getElementById('editStartDate').value = startDate;
                        document.getElementById('editStartTime').value = startTime;
                        document.getElementById('editEndDate').value = endDate;
                        document.getElementById('editEndTime').value = endTime;
                        document.getElementById('editStatus').value = plan.TrangThai || 'Chưa bắt đầu';
                        
                        // Load staff options and set selected staff
                        loadStaffOptions().then(() => {
                            if (plan.ID_NhanVien) {
                                document.getElementById('editAssignedStaff').value = plan.ID_NhanVien;
                            }
                        });
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
                        modal.show();
                    } else {
                        alert('Không thể tải thông tin kế hoạch: ' + (data.error || 'Lỗi không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error loading plan:', error);
                    alert('Có lỗi xảy ra khi tải thông tin kế hoạch');
                });
        }

        // Read data-* from button to open modal safely
        function openEditPlanFromButton(btn) {
            const planData = {
                id: btn.getAttribute('data-plan-id') || '',
                name: btn.getAttribute('data-plan-name') || '',
                content: btn.getAttribute('data-plan-content') || '',
                start: btn.getAttribute('data-start') || '',
                end: btn.getAttribute('data-end') || '',
                status: btn.getAttribute('data-status') || 'Chưa bắt đầu'
            };
            openEditPlanModal(planData);
        }

        // Submit edit plan
        function submitEditPlan() {
            const planId = document.getElementById('editPlanId').value;
            const name = document.getElementById('editPlanName').value.trim();
            const content = document.getElementById('editPlanContent').value.trim();
            const startDate = document.getElementById('editStartDate').value;
            const startTime = document.getElementById('editStartTime').value;
            const endDate = document.getElementById('editEndDate').value;
            const endTime = document.getElementById('editEndTime').value;
            const status = document.getElementById('editStatus').value;

            if (!planId || !name || !content || !startDate || !startTime || !endDate || !endTime) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }

            const startDateTime = `${startDate} ${startTime}`;
            const endDateTime = `${endDate} ${endTime}`;
            if (new Date(endDateTime) <= new Date(startDateTime)) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_plan');
            formData.append('planId', planId);
            formData.append('planName', name);
            formData.append('planContent', content);
            formData.append('startDateTime', startDateTime);
            formData.append('endDateTime', endDateTime);
            formData.append('status', status);
            formData.append('managerId', document.getElementById('editAssignedStaff').value || '');

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    // Update in local list
                    const idx = existingPlans.findIndex(p => (p.ID_KeHoach == planId || p.id == planId));
                    if (idx !== -1) {
                        existingPlans[idx].ten_kehoach = name;
                        existingPlans[idx].NoiDung = content;
                        existingPlans[idx].NgayBatDau = startDateTime;
                        existingPlans[idx].NgayKetThuc = endDateTime;
                        existingPlans[idx].TrangThai = status;
                        // also update assigned staff name/id
                        existingPlans[idx].ID_NhanVien = document.getElementById('editAssignedStaff').value || null;
                        const staffName = getStaffName(existingPlans[idx].ID_NhanVien);
                        if (staffName && staffName !== 'Chưa phân công') {
                            existingPlans[idx].TenNhanVien = staffName;
                        }
                    }
                    displayPlans();
                    updateStatistics();
                    approvedEvents.forEach(ev => loadEventPlans(ev.ID_DatLich));

                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPlanModal'));
                    modal.hide();
                    alert('Cập nhật kế hoạch thành công');
                } else {
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi cập nhật kế hoạch');
            });
        }
        
        // Update statistics
        function updateStatistics() {
            document.getElementById('approvedEventsCount').textContent = approvedEvents.length;
            document.getElementById('totalPlansCount').textContent = existingPlans.length;
            
            const inProgressCount = existingPlans.filter(p => p.TrangThai === 'Đang thực hiện').length;
            const completedCount = existingPlans.filter(p => p.TrangThai === 'Hoàn thành').length;
            
            document.getElementById('inProgressPlansCount').textContent = inProgressCount;
            document.getElementById('completedPlansCount').textContent = completedCount;
        }
        
        // Show error message for events
        function showEventsError() {
            document.getElementById('eventsList').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h4>Lỗi khi tải dữ liệu</h4>
                        <p>Không thể tải danh sách sự kiện. Vui lòng thử lại sau.</p>
                        <button class="btn btn-primary" onclick="loadPageData()">Thử lại</button>
                    </div>
                </div>
            `;
        }
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createPlan(eventId, eventName) {
            // Check if event is completed
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể tạo kế hoạch mới');
                return;
            }
            
            // Check if event already has a plan
            fetch(`../src/controllers/event-planning.php?action=get_plans&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.plans && data.plans.length > 0) {
                    alert('Sự kiện này đã có kế hoạch. Mỗi sự kiện chỉ được tạo 1 kế hoạch.');
                    return;
                }
                
                console.log('Creating plan for eventId:', eventId, 'eventName:', eventName);
                document.getElementById('eventId').value = eventId;
                document.querySelector('#createPlanModal .modal-title').innerHTML = 
                    '<i class="fas fa-plus-circle"></i> Tạo kế hoạch cho: ' + eventName;
                
                // Load staff options
                loadStaffOptions();
                
                // Set default dates and times
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('startDate').value = today;
                document.getElementById('startTime').value = '08:00';
                document.getElementById('endTime').value = '17:00';
                
                // Clear form fields
                document.getElementById('planName').value = '';
                document.getElementById('planContent').value = '';
                document.getElementById('endDate').value = '';
                document.getElementById('assignedStaff').value = '';
                
                const modal = new bootstrap.Modal(document.getElementById('createPlanModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error checking existing plans:', error);
                alert('Có lỗi xảy ra khi kiểm tra kế hoạch hiện có');
            });
        }

        function loadStaffOptions() {
            return fetch('../src/controllers/event-planning.php?action=get_staff', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        const selects = [];
                        const s1 = document.getElementById('assignedStaff');
                        const s2 = document.getElementById('editAssignedStaff');
                        if (s1) selects.push(s1);
                        if (s2) selects.push(s2);
                        selects.forEach(select => {
                            select.innerHTML = '<option value="">Chọn nhân viên</option>';
                            data.staff.forEach(staff => {
                                select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                            });
                        });
                    }
                    return data.staff || [];
                })
                .catch(error => {
                    console.error('Error loading staff:', error);
                    return [];
                });
        }

        function savePlan() {
            const form = document.getElementById('createPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'create_plan');
            
            // Check required fields
            const eventId = formData.get('eventId');
            const planName = formData.get('planName');
            const planContent = formData.get('planContent');
            const startDate = formData.get('startDate');
            const startTime = formData.get('startTime');
            const endDate = formData.get('endDate');
            const endTime = formData.get('endTime');
            
            if (!eventId || !planName || !planContent || !startDate || !startTime || !endDate || !endTime) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            // Update form data with combined datetime
            formData.set('startDateTime', startDateTime);
            formData.set('endDateTime', endDateTime);
            
            // Validate dates and times
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (endDateObj <= startDateObj) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Tạo kế hoạch thành công! Bây giờ bạn có thể thêm các bước thực hiện.');
                    
                    // Add new plan to existing plans array
                    const newPlan = {
                        ID_KeHoach: data.planId || Date.now(), // Use returned ID or fallback
                        ten_kehoach: formData.get('planName'),
                        NoiDung: formData.get('planContent'),
                        NgayBatDau: formData.get('startDateTime'),
                        NgayKetThuc: formData.get('endDateTime'),
                        TrangThai: 'Chưa thực hiện',
                        ID_DatLich: formData.get('eventId'), // Use ID_DatLich to match the query
                        ten_nhanvien: getStaffName(formData.get('assignedStaff'))
                    };
                    
                    existingPlans.unshift(newPlan); // Add to beginning of array
                    
                    // Update display immediately
                    displayPlans();
                    updateStatistics();
                    
                    // Update plans for the specific event
                    loadEventPlans(formData.get('eventId'));
                    
                    // Close create plan modal
                    const createModal = bootstrap.Modal.getInstance(document.getElementById('createPlanModal'));
                    createModal.hide();
                    
                    // Get event info for manage steps
                    const eventId = document.getElementById('eventId').value;
                    const eventName = document.querySelector('#createPlanModal .modal-title').textContent.replace('Tạo kế hoạch cho: ', '');
                    
                    // Open manage steps modal
                    setTimeout(() => {
                        manageSteps(eventId, eventName);
                    }, 500);
                    
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo kế hoạch: ' + error.message);
            });
        }

        // Reset step form
        function resetStepForm() {
            document.getElementById('addStepForm').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
            document.getElementById('stepStartTime').value = '08:00';
            document.getElementById('stepEndDate').value = today;
            document.getElementById('stepEndTime').value = '17:00';
            
            // Clear checkbox staff selection
            const stepStaffCheckboxes = document.querySelectorAll('#stepStaffCheckboxes input[type="checkbox"]');
            stepStaffCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Helper function to get staff name by ID
        function getStaffName(staffId) {
            if (!staffId) return 'Chưa phân công';
            
            // Try to find staff name from the select options
            const staffSelect = document.getElementById('assignedStaff');
            if (staffSelect) {
                const selectedOption = staffSelect.querySelector(`option[value="${staffId}"]`);
                if (selectedOption) {
                    return selectedOption.textContent.split(' - ')[0]; // Get name part only
                }
            }
            
            return 'Nhân viên #' + staffId;
        }

        function manageSteps(eventId, eventName) {
            document.getElementById('stepEventId').value = eventId;
            
            // Check if event is completed
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            const isEventCompleted = event && event.TrangThaiSuKien === 'Hoàn thành';
            
            // Check if all steps are completed by loading steps first
            fetch(`../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                let allStepsCompleted = false;
                if (data.success && data.steps && data.steps.length > 0) {
                    const totalSteps = data.steps.length;
                    const completedSteps = data.steps.filter(step => step.TrangThai === 'Hoàn thành').length;
                    allStepsCompleted = totalSteps > 0 && completedSteps === totalSteps;
                }
                
                const isReadOnly = isEventCompleted || allStepsCompleted;
                
                if (isReadOnly) {
                    const reason = isEventCompleted ? 'Sự kiện đã hoàn thành' : 'Tất cả các bước đã hoàn thành';
                    document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                        '<i class="fas fa-eye"></i> Xem bước thực hiện: ' + eventName + ' <span class="badge bg-success">' + reason + '</span>';
                } else {
                    document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                        '<i class="fas fa-cogs"></i> Quản lý bước thực hiện: ' + eventName;
                }
                
                // Load existing steps
                loadSteps(eventId, isReadOnly);
                
                // Load staff options (only if not read-only)
                if (!isReadOnly) {
                    loadStaffOptionsForSteps();
                    
                    // Set default dates and times
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    document.getElementById('stepStartTime').value = '08:00';
                    document.getElementById('stepEndDate').value = today;
                    document.getElementById('stepEndTime').value = '17:00';
                    
                    // Show add step form
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = '';
                    }
                } else {
                    // Hide add step form if read-only
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = 'none';
                    }
                }
                
                const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error checking steps:', error);
                // Fallback to loading steps normally
                loadSteps(eventId, isEventCompleted);
                
                if (!isEventCompleted) {
                    loadStaffOptionsForSteps();
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = '';
                    }
                } else {
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = 'none';
                    }
                }
                
                const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
                modal.show();
            });
        }

        function loadSteps(eventId, isReadOnly = false) {
            console.log('Loading steps for event:', eventId, 'isReadOnly:', isReadOnly);
            fetch(`../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Steps data:', data);
                    if (data.success && data.steps && data.steps.length > 0) {
                        // Calculate completion percentage
                        const totalSteps = data.steps.length;
                        const completedSteps = data.steps.filter(step => step.TrangThai === 'Hoàn thành').length;
                        const inProgressSteps = data.steps.filter(step => step.TrangThai === 'Đang làm').length;
                        const completionPercentage = totalSteps > 0 ? Math.round((completedSteps / totalSteps) * 100) : 0;
                        
                        // Check if all steps are completed - if so, set isReadOnly to true
                        const allStepsCompleted = totalSteps > 0 && completedSteps === totalSteps;
                        if (allStepsCompleted) {
                            isReadOnly = true;
                        }
                        
                        // Build progress bar HTML
                        let html = `
                            <div class="mb-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">
                                                <i class="fas fa-tasks me-2 text-primary"></i>
                                                <strong>Tiến độ hoàn thành</strong>
                                            </h6>
                                            <span class="badge bg-primary fs-6">${completionPercentage}%</span>
                                        </div>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar ${completionPercentage === 100 ? 'bg-success' : completionPercentage > 0 ? 'bg-warning progress-bar-striped progress-bar-animated' : 'bg-secondary'}" 
                                                 role="progressbar" 
                                                 style="width: ${completionPercentage}%" 
                                                 aria-valuenow="${completionPercentage}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                ${completionPercentage}%
                                            </div>
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between text-muted small">
                                            <span><i class="fas fa-check-circle text-success me-1"></i>Hoàn thành: ${completedSteps}/${totalSteps}</span>
                                            <span><i class="fas fa-spinner text-warning me-1"></i>Đang làm: ${inProgressSteps}</span>
                                            <span><i class="fas fa-clock text-secondary me-1"></i>Chưa làm: ${totalSteps - completedSteps - inProgressSteps}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        html += '<div class="timeline">';
                        data.steps.forEach((step, index) => {
                            const statusClass = step.TrangThai === 'Hoàn thành' ? 'success' : 
                                             step.TrangThai === 'Đang làm' ? 'warning' : 'secondary';
                            
                            // Parse progress percentage from Tiendo (format: "50%" or "0%")
                            let progressValue = 0;
                            if (step.Tiendo) {
                                const progressMatch = step.Tiendo.toString().match(/(\d+)/);
                                progressValue = progressMatch ? parseInt(progressMatch[1]) : 0;
                            }
                            
                            // Determine progress bar color and animation
                            let progressBarClass = 'bg-secondary';
                            if (progressValue >= 100) {
                                progressBarClass = 'bg-success';
                            } else if (progressValue > 0) {
                                progressBarClass = 'bg-warning progress-bar-striped progress-bar-animated';
                            }
                            
                            html += `
                                <div class="timeline-item">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <span class="badge bg-primary me-2">Bước ${index + 1}</span>
                                                    ${step.TenBuoc}
                                                </h6>
                                                <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- Progress Bar for this step -->
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-end align-items-center mb-2">
                                                    <span class="badge ${progressValue >= 100 ? 'bg-success' : progressValue > 0 ? 'bg-warning' : 'bg-secondary'} fs-6">${progressValue}%</span>
                                                </div>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar ${progressBarClass}" 
                                                         role="progressbar" 
                                                         style="width: ${progressValue}%" 
                                                         aria-valuenow="${progressValue}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        ${progressValue}%
                                                    </div>
                                                </div>
                                                ${step.GhiChuTienDo ? `
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fas fa-comment me-1"></i>${step.GhiChuTienDo}
                                                </small>
                                                ` : ''}
                                            </div>
                                            
                                            <div class="row">
                                                <div class="${isReadOnly ? 'col-12' : 'col-md-8'}">
                                                    ${step.MoTa ? `
                                                    <div class="mb-3">
                                                        <h6 class="text-primary"><i class="fas fa-info-circle"></i> Mô tả</h6>
                                                        <p class="text-muted mb-0">${step.MoTa}</p>
                                                    </div>
                                                    ` : ''}
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6 class="text-success"><i class="fas fa-calendar-alt"></i> Thời gian</h6>
                                                            <p class="mb-1"><strong>Bắt đầu:</strong> ${new Date(step.NgayBatDau).toLocaleString('vi-VN')}</p>
                                                            <p class="mb-0"><strong>Kết thúc:</strong> ${new Date(step.NgayKetThuc).toLocaleString('vi-VN')}</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="text-info"><i class="fas fa-users"></i> Nhân viên</h6>
                                                            ${step.assignedStaff && step.assignedStaff.length > 0 ? `
                                                            <div>
                                                                ${step.assignedStaff.map(staff => `
                                                                    <div class="mb-2 p-2 bg-light rounded">
                                                                        <p class="mb-1"><strong>${escapeHtml(staff.HoTen || 'N/A')}</strong></p>
                                                                        ${staff.ChucVu ? `<p class="mb-0 text-secondary small"><i class="fas fa-briefcase me-1"></i>${escapeHtml(staff.ChucVu)}</p>` : ''}
                                                                    </div>
                                                                `).join('')}
                                                            </div>
                                                            ` : step.TenNhanVien ? `
                                                            <div class="d-flex align-items-center">
                                                                <div>
                                                                    <p class="mb-1"><strong>${escapeHtml(step.TenNhanVien)}</strong></p>
                                                                    ${step.ChucVu ? `<p class="mb-0 text-secondary small"><i class="fas fa-briefcase me-1"></i>${escapeHtml(step.ChucVu)}</p>` : ''}
                                                                </div>
                                                            </div>
                                                            ` : `
                                                            <div class="text-center text-muted">
                                                                <i class="fas fa-user-slash fa-2x mb-2"></i>
                                                                <p class="mb-0">Chưa phân công nhân viên</p>
                                                                <small>Click "Chỉnh sửa" để phân công</small>
                                                            </div>
                                                            `}
                                                        </div>
                                                    </div>
                                                    
                                                    ${(() => {
                                                        // Extract note from MoTa if it contains [Ghi chú: ...]
                                                        let note = '';
                                                        if (step.MoTa) {
                                                            const noteMatch = step.MoTa.match(/\[Ghi chú:\s*([^\]]+)\]/);
                                                            if (noteMatch) {
                                                                note = noteMatch[1].trim();
                                                            }
                                                        }
                                                        return note ? `
                                                    <div class="mb-3">
                                                        <h6 class="text-warning"><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                                        <div class="alert alert-light py-2">
                                                            <p class="mb-0">${escapeHtml(note)}</p>
                                                        </div>
                                                    </div>
                                                    ` : '';
                                                    })()}
                                                    
                                                    ${isReadOnly ? `
                                                    <div class="mt-3">
                                                        <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                                                            <i class="fas fa-check-circle fa-2x me-3"></i>
                                                            <div>
                                                                <strong>Sự kiện đã hoàn thành</strong>
                                                                <p class="mb-0 small">Bạn đang xem ở chế độ chỉ đọc. Không thể chỉnh sửa hoặc xóa các bước.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    ` : ''}
                                                </div>
                                                
                                                ${!isReadOnly ? `
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <h6 class="text-primary mb-3">Thao tác</h6>
                                                        <div class="d-grid gap-2">
                                                            <button class="btn btn-outline-success btn-sm" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Hoàn thành')" title="Hoàn thành">
                                                                <i class="fas fa-check me-1"></i>Hoàn thành
                                                            </button>
                                                            <button class="btn btn-outline-warning btn-sm" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Đang làm')" title="Đang làm">
                                                                <i class="fas fa-play me-1"></i>Đang làm
                                                            </button>
                                                            <button class="btn btn-outline-info btn-sm" onclick="editStep(${step.ID_ChiTiet})" title="Chỉnh sửa">
                                                                <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                                            </button>
                                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteStep(${step.ID_ChiTiet})" title="Xóa">
                                                                <i class="fas fa-trash me-1"></i>Xóa
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        document.getElementById('stepsList').innerHTML = html;
                    } else {
                        document.getElementById('stepsList').innerHTML = '<p class="text-muted">Chưa có bước thực hiện nào.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading steps:', error);
                    document.getElementById('stepsList').innerHTML = '<p class="text-danger">Lỗi khi tải danh sách bước thực hiện.</p>';
                });
        }

        function editStep(stepId) {
            // Check if event is completed
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể chỉnh sửa bước');
                return;
            }
            
            console.log('Editing step:', stepId);
            
            // Fetch step details
            fetch(`../src/controllers/event-planning.php?action=get_step&step_id=${stepId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Step data response:', data);
                    if (data.success && data.step) {
                        const step = data.step;
                        console.log('Step object:', step);
                        
                        // Fill edit step modal
                        document.getElementById('editStepId').value = step.ID_ChiTiet;
                        document.getElementById('editStepName').value = step.TenBuoc || '';
                        
                        // Extract description and note from MoTa
                        // Note: GhiChu column doesn't exist, so we parse from MoTa if it contains [Ghi chú: ...]
                        let description = step.MoTa || '';
                        let note = '';
                        
                        // Check if MoTa contains note in format [Ghi chú: ...]
                        const noteMatch = description.match(/\[Ghi chú:\s*([^\]]+)\]/);
                        if (noteMatch) {
                            note = noteMatch[1].trim();
                            // Remove note from description
                            description = description.replace(/\s*\[Ghi chú:[^\]]+\]\s*$/, '').trim();
                        }
                        
                        document.getElementById('editStepDescription').value = description;
                        
                        // Handle GhiChu field (extracted from MoTa)
                        const stepNoteElement = document.getElementById('editStepNote');
                        if (stepNoteElement) {
                            stepNoteElement.value = note;
                        }
                        
                        // Split datetime
                        const startDate = step.NgayBatDau ? step.NgayBatDau.split(' ')[0] : '';
                        const startTime = step.NgayBatDau ? step.NgayBatDau.split(' ')[1] : '08:00';
                        const endDate = step.NgayKetThuc ? step.NgayKetThuc.split(' ')[0] : '';
                        const endTime = step.NgayKetThuc ? step.NgayKetThuc.split(' ')[1] : '17:00';
                        
                        document.getElementById('editStepStartDate').value = startDate;
                        document.getElementById('editStepStartTime').value = startTime;
                        document.getElementById('editStepEndDate').value = endDate;
                        document.getElementById('editStepEndTime').value = endTime;
                        
                        // Load staff options and set selected staff (multiple checkboxes)
                        loadStaffOptionsForSteps().then(() => {
                            // Clear previous selections
                            const editCheckboxes = document.querySelectorAll('#editStepStaffCheckboxes input[type="checkbox"]');
                            editCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            
                            // If step has assignedStaff array, check all assigned staff
                            if (step.assignedStaff && Array.isArray(step.assignedStaff) && step.assignedStaff.length > 0) {
                                step.assignedStaff.forEach(staff => {
                                    const checkbox = document.getElementById(`edit_staff_${staff.ID_NhanVien}`);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                    }
                                });
                            } else if (step.ID_NhanVien) {
                                // Fallback to single staff (backward compatibility)
                                const checkbox = document.getElementById(`edit_staff_${step.ID_NhanVien}`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            }
                        });
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editStepModal'));
                        modal.show();
                    } else {
                        console.error('Failed to load step data:', data);
                        alert('Không thể tải thông tin bước thực hiện: ' + (data.error || 'Lỗi không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error loading step:', error);
                    alert('Có lỗi xảy ra khi tải thông tin bước thực hiện: ' + error.message);
                });
        }

        function submitEditStep() {
            // Check if event is completed
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể chỉnh sửa bước');
                return;
            }
            
            const stepIdElement = document.getElementById('editStepId');
            const stepNameElement = document.getElementById('editStepName');
            const stepDescriptionElement = document.getElementById('editStepDescription');
            const stepStartDateElement = document.getElementById('editStepStartDate');
            const stepStartTimeElement = document.getElementById('editStepStartTime');
            const stepEndDateElement = document.getElementById('editStepEndDate');
            const stepEndTimeElement = document.getElementById('editStepEndTime');
            const stepNoteElement = document.getElementById('editStepNote');
            
            // Check if all required elements exist (staff is optional, loaded from checkboxes)
            if (!stepIdElement || !stepNameElement || !stepDescriptionElement || 
                !stepStartDateElement || !stepStartTimeElement || 
                !stepEndDateElement || !stepEndTimeElement) {
                console.error('Missing form elements:', {
                    stepIdElement: !!stepIdElement,
                    stepNameElement: !!stepNameElement,
                    stepDescriptionElement: !!stepDescriptionElement,
                    stepStartDateElement: !!stepStartDateElement,
                    stepStartTimeElement: !!stepStartTimeElement,
                    stepEndDateElement: !!stepEndDateElement,
                    stepEndTimeElement: !!stepEndTimeElement
                });
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            const stepId = stepIdElement.value;
            const stepName = stepNameElement.value.trim();
            const stepDescription = stepDescriptionElement.value.trim();
            const stepStartDate = stepStartDateElement.value;
            const stepStartTime = stepStartTimeElement.value;
            const stepEndDate = stepEndDateElement.value;
            const stepEndTime = stepEndTimeElement.value;
            const stepNote = stepNoteElement ? stepNoteElement.value.trim() : '';
            
            // Get all selected staff IDs from checkboxes
            const stepStaffCheckboxes = document.querySelectorAll('#editStepStaffCheckboxes input[type="checkbox"]:checked');
            const selectedStaffIds = Array.from(stepStaffCheckboxes)
                .map(checkbox => checkbox.value)
                .filter(value => value !== '' && value !== null); // Remove empty values
            
            // Validate required fields
            if (!stepName) {
                alert('Vui lòng nhập tên bước');
                return;
            }
            
            if (!stepStartDate || !stepStartTime || !stepEndDate || !stepEndTime) {
                alert('Vui lòng chọn đầy đủ ngày và giờ bắt đầu, kết thúc');
                return;
            }
            
            const startDateTime = `${stepStartDate} ${stepStartTime}`;
            const endDateTime = `${stepEndDate} ${stepEndTime}`;
            
            // Validate datetime
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (isNaN(startDateObj.getTime()) || isNaN(endDateObj.getTime())) {
                alert('Ngày giờ không hợp lệ. Vui lòng kiểm tra lại.');
                return;
            }
            
            if (endDateObj <= startDateObj) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_step');
            formData.append('stepId', stepId);
            formData.append('stepName', stepName);
            formData.append('stepDescription', stepDescription);
            formData.append('stepStartDateTime', startDateTime);
            formData.append('stepEndDateTime', endDateTime);
            
            // Add note if provided
            if (stepNote) {
                formData.append('stepNote', stepNote);
            }
            
            // Add all selected staff IDs
            selectedStaffIds.forEach(staffId => {
                formData.append('staffId[]', staffId);
            });
            
            // Also set as staffId for backward compatibility
            if (selectedStaffIds.length > 0) {
                formData.append('staffId', selectedStaffIds.join(','));
            }
            
            console.log('Sending update step data:', {
                action: 'update_step',
                stepId: stepId,
                stepName: stepName,
                stepDescription: stepDescription,
                stepStartDateTime: startDateTime,
                stepEndDateTime: endDateTime,
                staffIds: selectedStaffIds
            });
            
            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Update step response:', data);
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editStepModal'));
                    modal.hide();
                    alert('Cập nhật bước thực hiện thành công');
                    
                    // Reload steps for current event
                    const stepEventIdElement = document.getElementById('stepEventId');
                    if (stepEventIdElement) {
                        const currentEventId = stepEventIdElement.value;
                        if (currentEventId) {
                            // Check if event is completed to determine read-only mode
                            const event = approvedEvents.find(e => e.ID_DatLich == currentEventId);
                            const isReadOnly = event && event.TrangThaiSuKien === 'Hoàn thành';
                            loadSteps(currentEventId, isReadOnly);
                        }
                    } else {
                        console.warn('stepEventId element not found, skipping step reload');
                    }
                } else {
                    console.error('Update step failed:', data);
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error updating step:', error);
                alert('Có lỗi xảy ra khi cập nhật bước thực hiện: ' + error.message);
            });
        }

        function loadStaffOptionsForSteps() {
            return fetch('../src/controllers/event-planning.php?action=get_staff_list', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    const addContainer = document.getElementById('stepStaffCheckboxes');
                    const editContainer = document.getElementById('editStepStaffCheckboxes');
                    
                    // Clear containers
                    if (addContainer) addContainer.innerHTML = '';
                    if (editContainer) editContainer.innerHTML = '';
                    
                    if (data.success && data.staff && data.staff.length > 0) {
                        // Populate add step form
                        if (addContainer) {
                            data.staff.forEach(staff => {
                                const checkboxItem = document.createElement('div');
                                checkboxItem.className = 'staff-checkbox-item';
                                checkboxItem.innerHTML = `
                                    <input type="checkbox" id="staff_${staff.ID_NhanVien}" name="staffId[]" value="${staff.ID_NhanVien}">
                                    <label for="staff_${staff.ID_NhanVien}">
                                        <span class="staff-name">${escapeHtml(staff.HoTen)}</span>
                                        <span class="staff-role">${escapeHtml(staff.ChucVu)}</span>
                                    </label>
                                `;
                                addContainer.appendChild(checkboxItem);
                            });
                        }
                        
                        // Populate edit step form
                        if (editContainer) {
                            data.staff.forEach(staff => {
                                const checkboxItem = document.createElement('div');
                                checkboxItem.className = 'staff-checkbox-item';
                                checkboxItem.innerHTML = `
                                    <input type="checkbox" id="edit_staff_${staff.ID_NhanVien}" name="staffId[]" value="${staff.ID_NhanVien}">
                                    <label for="edit_staff_${staff.ID_NhanVien}">
                                        <span class="staff-name">${escapeHtml(staff.HoTen)}</span>
                                        <span class="staff-role">${escapeHtml(staff.ChucVu)}</span>
                                    </label>
                                `;
                                editContainer.appendChild(checkboxItem);
                            });
                        }
                    } else {
                        // Show no staff message
                        const noStaffMsg = '<div class="text-center text-muted py-3"><i class="fas fa-user-slash"></i> Không có nhân viên nào</div>';
                        if (addContainer) addContainer.innerHTML = noStaffMsg;
                        if (editContainer) editContainer.innerHTML = noStaffMsg;
                    }
                    
                    return Promise.resolve();
                })
                .catch(error => {
                    console.error('Error loading staff:', error);
                    const errorMsg = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle"></i> Lỗi khi tải danh sách nhân viên</div>';
                    const addContainer = document.getElementById('stepStaffCheckboxes');
                    const editContainer = document.getElementById('editStepStaffCheckboxes');
                    if (addContainer) addContainer.innerHTML = errorMsg;
                    if (editContainer) editContainer.innerHTML = errorMsg;
                    return Promise.reject(error);
                });
        }

        function addStep() {
            // Check if event is completed
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể thêm bước mới');
                return;
            }
            
            const form = document.getElementById('addStepForm');
            const stepEventIdElement = document.getElementById('stepEventId');
            
            if (!form) {
                console.error('addStepForm not found');
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            if (!stepEventIdElement) {
                console.error('stepEventId element not found');
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            const formData = new FormData(form);
            formData.append('action', 'add_event_step');
            formData.append('eventId', stepEventIdElement.value);
            
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

            // Get all selected staff IDs from checkboxes
            const stepStaffCheckboxes = document.querySelectorAll('#stepStaffCheckboxes input[type="checkbox"]:checked');
            const selectedStaffIds = Array.from(stepStaffCheckboxes)
                .map(checkbox => checkbox.value)
                .filter(value => value !== ''); // Remove empty values
            
            // Remove old staffId entries and add new ones
            formData.delete('staffId[]');
            selectedStaffIds.forEach(staffId => {
                formData.append('staffId[]', staffId);
            });
            
            // Also set as stepStaff for backward compatibility
            if (selectedStaffIds.length > 0) {
                formData.set('stepStaff', selectedStaffIds.join(','));
            }
            
            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Thêm bước thực hiện thành công');
                    form.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    document.getElementById('stepStartTime').value = '08:00';
                    document.getElementById('stepEndTime').value = '17:00';
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

        function updateStepStatus(stepId, status) {
            // Check if event is completed
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể cập nhật trạng thái');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_step_status');
            formData.append('step_id', stepId);
            formData.append('status', status);

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
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
            // Check if event is completed
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể xóa bước');
                return;
            }
            
            if (confirm('Bạn có chắc muốn xóa bước thực hiện này?')) {
                const formData = new FormData();
                formData.append('action', 'delete_step');
                formData.append('step_id', stepId);

            fetch('../src/controllers/event-planning.php', {
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

        // Helper function to handle fetch responses
        function handleFetchResponse(response) {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        }
    </script>
</body>
</html>
