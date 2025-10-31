<?php
/**
 * SePay Callback Redirect
 * File này để redirect từ callback URL cũ sang webhook handler mới
 */

// Redirect to the actual webhook handler
$webhookUrl = 'https://sukien.info.vn/event/my-php-project/hooks/sepay-payment.php';

// Log the redirect
error_log("SePay Callback redirect from: " . $_SERVER['REQUEST_URI'] . " to: " . $webhookUrl);

// Forward the request to the actual webhook handler
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: SePay-Callback-Redirect/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Set the same HTTP status code
http_response_code($httpCode);

// Return the same response
echo $response;

// Log the result
if ($error) {
    error_log("SePay Callback redirect error: " . $error);
} else {
    error_log("SePay Callback redirect success: HTTP {$httpCode}");
}
?>