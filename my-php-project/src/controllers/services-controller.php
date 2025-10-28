<?php
require_once __DIR__ . '/../../config/database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_locations':
            getLocations($pdo);
            break;
            
        case 'get_equipment':
            getEquipment($pdo);
            break;
            
        case 'get_combos':
            getCombos($pdo);
            break;
            
        case 'get_all_services':
            getAllServices($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Services Controller Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getLocations($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                dd.*,
                COUNT(dl.ID_DatLich) as SoSuKienDaToChuc
            FROM diadiem dd
            LEFT JOIN datlichsukien dl ON dd.ID_DD = dl.ID_DD
            GROUP BY dd.ID_DD
            ORDER BY dd.TenDiaDiem ASC
        ");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'locations' => $locations]);
        
    } catch (Exception $e) {
        error_log("Error getting locations: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getEquipment($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                tb.*,
                COUNT(ctds.ID_CT) as SoLanSuDung
            FROM thietbi tb
            LEFT JOIN chitietdatsukien ctds ON tb.ID_TB = ctds.ID_TB
            GROUP BY tb.ID_TB
            ORDER BY tb.LoaiThietBi, tb.TenThietBi ASC
        ");
        $stmt->execute();
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'equipment' => $equipment]);
        
    } catch (Exception $e) {
        error_log("Error getting equipment: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getCombos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                COUNT(ctds.ID_CT) as SoLanSuDung,
                GROUP_CONCAT(
                    CONCAT(tb.TenThietBi, ' (', cc.SoLuong, ')') 
                    SEPARATOR ', '
                ) as ThietBiTrongCombo
            FROM combo c
            LEFT JOIN chitietdatsukien ctds ON c.ID_Combo = ctds.ID_Combo
            LEFT JOIN combochitiet cc ON c.ID_Combo = cc.ID_Combo
            LEFT JOIN thietbi tb ON cc.ID_TB = tb.ID_TB
            GROUP BY c.ID_Combo
            ORDER BY c.GiaCombo ASC
        ");
        $stmt->execute();
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'combos' => $combos]);
        
    } catch (Exception $e) {
        error_log("Error getting combos: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getAllServices($pdo) {
    try {
        // Get locations
        $stmt = $pdo->prepare("
            SELECT 
                dd.*,
                COUNT(dl.ID_DatLich) as SoSuKienDaToChuc
            FROM diadiem dd
            LEFT JOIN datlichsukien dl ON dd.ID_DD = dl.ID_DD
            GROUP BY dd.ID_DD
            ORDER BY dd.TenDiaDiem ASC
        ");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get equipment
        $stmt = $pdo->prepare("
            SELECT 
                tb.*,
                COUNT(ctds.ID_CT) as SoLanSuDung
            FROM thietbi tb
            LEFT JOIN chitietdatsukien ctds ON tb.ID_TB = ctds.ID_TB
            GROUP BY tb.ID_TB
            ORDER BY tb.LoaiThietBi, tb.TenThietBi ASC
        ");
        $stmt->execute();
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get combos
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                COUNT(ctds.ID_CT) as SoLanSuDung,
                GROUP_CONCAT(
                    CONCAT(tb.TenThietBi, ' (', cc.SoLuong, ')') 
                    SEPARATOR ', '
                ) as ThietBiTrongCombo
            FROM combo c
            LEFT JOIN chitietdatsukien ctds ON c.ID_Combo = ctds.ID_Combo
            LEFT JOIN combochitiet cc ON c.ID_Combo = cc.ID_Combo
            LEFT JOIN thietbi tb ON cc.ID_TB = tb.ID_TB
            GROUP BY c.ID_Combo
            ORDER BY c.GiaCombo ASC
        ");
        $stmt->execute();
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'locations' => $locations,
            'equipment' => $equipment,
            'combos' => $combos
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting all services: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
