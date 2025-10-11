<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();

$customerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5")->fetchColumn();
$staffCount = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role IN (2,3,4)")->fetchColumn();

echo json_encode([
    'customerCount' => $customerCount,
    'staffCount' => $staffCount
]);
?>