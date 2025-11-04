<?php
// Load .env
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Set default values if .env doesn't exist
if (!file_exists(__DIR__ . '/../.env')) {
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'event';
    $_ENV['DB_USER'] = 'root';
    $_ENV['DB_PASS'] = '';
    // Generate a secure random JWT secret if not exists
    if (!isset($_ENV['JWT_SECRET'])) {
        $_ENV['JWT_SECRET'] = bin2hex(random_bytes(32));
    }
} else {
    loadEnv(__DIR__ . '/../.env');
}

// Define constants
define('JWT_SECRET', $_ENV['JWT_SECRET']);

// Base URL configuration - Auto detect from server
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);

// Remove filename if present
$basePath = str_replace(basename($scriptName), '', $basePath);
$basePath = rtrim($basePath, '/');

// If basePath is empty or just '/', use root
if (empty($basePath) || $basePath === '/') {
    $basePath = '';
} else {
    $basePath = $basePath;
}

// Base URL (for API calls)
define('BASE_URL', $protocol . '://' . $host . $basePath);

// Base Path (for assets, relative paths)
define('BASE_PATH', $basePath);