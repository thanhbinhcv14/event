<?php
// function getDBConnection() {
//     $host = 'localhost';
//     $username = 'root';
//     $password = '';
//     $database = 'event';

//     $conn = new mysqli($host, $username, $password, $database);

//     if ($conn->connect_error) {
//         die("Kết nối thất bại: " . $conn->connect_error);
//     }

//     $conn->set_charset("utf8mb4");
//     return $conn;
// }

require_once __DIR__ . '/config.php';

function getDBConnection() {
    // Kiểm tra biến môi trường
    if (!isset($_ENV['DB_HOST']) || !isset($_ENV['DB_NAME']) || !isset($_ENV['DB_USER'])) {
        throw new Exception('Database configuration không đầy đủ. Vui lòng kiểm tra file .env hoặc config.php');
    }
    
    $host = $_ENV['DB_HOST'];
    $db   = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'] ?? '';
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5 // Timeout 5 giây
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Log lỗi nhưng không die() - để controller xử lý
        error_log('Database connection error: ' . $e->getMessage());
        throw new Exception('Không thể kết nối database. Vui lòng thử lại sau.');
    }
}
// $host = $_ENV['DB_HOST'];
//     $db   = $_ENV['DB_NAME'];
//     $user = $_ENV['DB_USER'];
//     $pass = $_ENV['DB_PASS'];