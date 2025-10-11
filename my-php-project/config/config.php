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