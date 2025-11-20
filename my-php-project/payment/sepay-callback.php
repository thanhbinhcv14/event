<?php
/**
 * SePay Callback Redirect
 * File này để redirect từ callback URL cũ sang webhook handler mới
 */

// Chuyển hướng đến webhook handler thực tế
$webhookUrl = 'https://sukien.info.vn/hooks/sepay-payment.php';

// Ghi log chuyển hướng
error_log("SePay Callback redirect from: " . $_SERVER['REQUEST_URI'] . " to: " . $webhookUrl);

// Chuyển tiếp request đến webhook handler thực tế
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

// Đặt cùng mã trạng thái HTTP
http_response_code($httpCode);

// Trả về cùng response
echo $response;

// Ghi log kết quả
if ($error) {
    error_log("SePay Callback redirect error: " . $error);
} else {
    error_log("SePay Callback redirect success: HTTP {$httpCode}");
}
?>