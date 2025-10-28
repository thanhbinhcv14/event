<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Kiểm tra cấu trúc bảng datlichsukien</h2>";
    
    // Kiểm tra cấu trúc cột TrangThaiThanhToan
    $stmt = $pdo->prepare("DESCRIBE datlichsukien");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'TrangThaiThanhToan') {
            echo "<tr style='background-color: yellow;'>";
        } else {
            echo "<tr>";
        }
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Thử UPDATE với transaction:</h3>";
    
    $pdo->beginTransaction();
    
    try {
        // UPDATE sự kiện 21
        $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Chờ thanh toán' WHERE ID_DatLich = 21");
        $stmt->execute();
        echo "<p>UPDATE sự kiện 21: Thành công</p>";
        
        // Kiểm tra ngay sau UPDATE
        $stmt = $pdo->prepare("SELECT TrangThaiThanhToan FROM datlichsukien WHERE ID_DatLich = 21");
        $stmt->execute();
        $status = $stmt->fetchColumn();
        echo "<p>Trạng thái trong transaction: [{$status}]</p>";
        
        $pdo->commit();
        echo "<p>Transaction committed thành công</p>";
        
        // Kiểm tra sau commit
        $stmt = $pdo->prepare("SELECT TrangThaiThanhToan FROM datlichsukien WHERE ID_DatLich = 21");
        $stmt->execute();
        $status = $stmt->fetchColumn();
        echo "<p>Trạng thái sau commit: [{$status}]</p>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p style='color: red;'>Transaction failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
