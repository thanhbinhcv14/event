<?php
/**
 * Test Webhook Endpoint
 * Dùng để kiểm tra webhook có thể truy cập được không
 */

header('Content-Type: application/json');

$response = [
    'success' => true,
    'message' => 'Webhook endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'UNKNOWN',
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'UNKNOWN',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN',
        'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'UNKNOWN',
    ],
    'headers' => [
        'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT SET',
        'Authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'SET (hidden)' : 'NOT SET',
        'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'NOT SET',
    ],
    'input' => [
        'raw_input_length' => strlen(file_get_contents('php://input')),
        'post_data' => !empty($_POST) ? 'Has POST data' : 'No POST data',
        'get_data' => !empty($_GET) ? 'Has GET data' : 'No GET data',
    ]
];

// Log test access
$logFile = __DIR__ . '/webhook_test_log.txt';
$logEntry = date('Y-m-d H:i:s') . " - Test webhook accessed\n";
$logEntry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n";
$logEntry .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
$logEntry .= str_repeat("-", 50) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

