<?php
// Test file để kiểm tra API thanh toán QR
session_start();

// Simulate logged in user
$_SESSION['user'] = [
    'ID_User' => 17,
    'Email' => 'test@example.com'
];

echo "<h2>Test Payment QR API</h2>";

// Test 1: Create Momo payment with official API
echo "<h3>Test 1: Tạo thanh toán MoMo chính thức</h3>";
$momoData = [
    'action' => 'create_momo_payment',
    'event_id' => 21,
    'amount' => 50000000,
    'payment_type' => 'deposit'
];

echo "<strong>Request:</strong><br>";
echo json_encode($momoData, JSON_PRETTY_PRINT) . "<br><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/event/my-php-project/src/controllers/payment.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($momoData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
curl_close($ch);

echo "<strong>Response:</strong><br>";
$momoResponse = json_decode($response, true);
echo json_encode($momoResponse, JSON_PRETTY_PRINT) . "<br><br>";

if ($momoResponse['success']) {
    $momoPaymentId = $momoResponse['payment_id'];
    echo "<strong>MoMo Pay URL:</strong> " . $momoResponse['pay_url'] . "<br>";
    echo "<strong>Order ID:</strong> " . $momoResponse['order_id'] . "<br>";
    echo "<strong>Transaction Code:</strong> " . $momoResponse['transaction_code'] . "<br><br>";
}

// Test 2: Create Banking payment
echo "<h3>Test 2: Tạo thanh toán Banking</h3>";
$bankingData = [
    'action' => 'create_payment',
    'event_id' => 21,
    'amount' => 100000000,
    'payment_method' => 'banking',
    'payment_type' => 'full'
];

echo "<strong>Request:</strong><br>";
echo json_encode($bankingData, JSON_PRETTY_PRINT) . "<br><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/event/my-php-project/src/controllers/payment.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bankingData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
curl_close($ch);

echo "<strong>Response:</strong><br>";
$bankingResponse = json_decode($response, true);
echo json_encode($bankingResponse, JSON_PRETTY_PRINT) . "<br><br>";

if ($bankingResponse['success']) {
    $bankingPaymentId = $bankingResponse['payment_id'];
    echo "<strong>Banking QR Code:</strong> " . $bankingResponse['qr_code'] . "<br>";
    echo "<strong>Banking QR Data:</strong><br>";
    echo json_encode($bankingResponse['qr_data'], JSON_PRETTY_PRINT) . "<br><br>";
}

// Test 3: Get QR Code
if (isset($momoPaymentId)) {
    echo "<h3>Test 3: Lấy mã QR thanh toán</h3>";
    $qrUrl = "http://localhost/event/my-php-project/src/controllers/payment.php?action=get_qr_code&payment_id=" . $momoPaymentId;
    
    echo "<strong>Request URL:</strong> " . $qrUrl . "<br><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $qrUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<strong>Response:</strong><br>";
    $qrResponse = json_decode($response, true);
    echo json_encode($qrResponse, JSON_PRETTY_PRINT) . "<br><br>";
}

// Test 4: Generate new QR
if (isset($bankingPaymentId)) {
    echo "<h3>Test 4: Tạo lại mã QR</h3>";
    $generateData = [
        'action' => 'generate_qr',
        'payment_id' => $bankingPaymentId
    ];
    
    echo "<strong>Request:</strong><br>";
    echo json_encode($generateData, JSON_PRETTY_PRINT) . "<br><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/event/my-php-project/src/controllers/payment.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($generateData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<strong>Response:</strong><br>";
    $generateResponse = json_decode($response, true);
    echo json_encode($generateResponse, JSON_PRETTY_PRINT) . "<br><br>";
}

// Test 5: Verify MoMo Payment
if (isset($momoResponse['order_id'])) {
    echo "<h3>Test 5: Kiểm tra trạng thái thanh toán MoMo</h3>";
    $verifyData = [
        'action' => 'verify_momo_payment',
        'order_id' => $momoResponse['order_id']
    ];
    
    echo "<strong>Request:</strong><br>";
    echo json_encode($verifyData, JSON_PRETTY_PRINT) . "<br><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/event/my-php-project/src/controllers/payment.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verifyData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<strong>Response:</strong><br>";
    $verifyResponse = json_decode($response, true);
    echo json_encode($verifyResponse, JSON_PRETTY_PRINT) . "<br><br>";
}

echo "<h3>Test hoàn thành!</h3>";
?>
