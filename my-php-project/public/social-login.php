<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/hybridauth.php';

$provider = $_GET['provider'] ?? '';
if (!$provider) die('No provider specified');

$hybridauth = new Hybridauth\Hybridauth($config);
$adapter = $hybridauth->authenticate($provider);
// Hybridauth sẽ tự động chuyển hướng về callback sau khi xác thực