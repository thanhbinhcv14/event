<?php
session_start();
require_once __DIR__ . '/../config/config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!isset($_POST['token'])) {
    http_response_code(400);
    echo 'Thiếu token';
    exit;
}

try {
    $decoded = JWT::decode($_POST['token'], new Key($_ENV['JWT_SECRET'], 'HS256'));
    $_SESSION['user'] = [
        'ID_User' => $decoded->sub,
        'id' => $decoded->sub,
        'Email' => $decoded->email,
        'email' => $decoded->email,
        'ID_Role' => $decoded->role,
        'role' => $decoded->role
    ];
    echo 'OK';
} catch (Exception $e) {
    http_response_code(401);
    echo 'Token không hợp lệ';
}
?>