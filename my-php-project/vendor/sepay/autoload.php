<?php

// SePay Autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = str_replace('SePay\\', '', $class);
    $file = str_replace('\\', '/', $file);
    $file = __DIR__ . '/' . $file . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Manual includes for immediate availability
require_once __DIR__ . '/SePayClient.php';
require_once __DIR__ . '/CheckoutService.php';
require_once __DIR__ . '/Builders/CheckoutBuilder.php';
?>