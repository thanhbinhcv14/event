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
?> 

<?php
require_once __DIR__ . '/config.php';

function getDBConnection() {
    $host = $_ENV['DB_HOST'];
    $db   = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Kết nối thất bại: " . $e->getMessage());
    }
}
// $host = $_ENV['DB_HOST'];
//     $db   = $_ENV['DB_NAME'];
//     $user = $_ENV['DB_USER'];
//     $pass = $_ENV['DB_PASS'];