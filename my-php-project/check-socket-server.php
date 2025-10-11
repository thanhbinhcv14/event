<?php
// Script to check Socket.IO server status
echo "<h2>Socket.IO Server Status Check</h2>";

$socketUrl = 'http://localhost:3000';
$timeout = 5;

echo "<h3>Checking Socket.IO Server at: $socketUrl</h3>";

// Method 1: Check if port is open
$connection = @fsockopen('localhost', 3000, $errno, $errstr, $timeout);
if ($connection) {
    echo "✅ Port 3000 is open<br>";
    fclose($connection);
} else {
    echo "❌ Port 3000 is closed or not responding<br>";
    echo "Error: $errstr ($errno)<br>";
}

// Method 2: Try to get Socket.IO endpoint
echo "<h3>Testing Socket.IO Endpoint</h3>";
$context = stream_context_create([
    'http' => [
        'timeout' => $timeout,
        'method' => 'GET'
    ]
]);

$socketIoUrl = $socketUrl . '/socket.io/socket.io.js';
$result = @file_get_contents($socketIoUrl, false, $context);

if ($result !== false) {
    echo "✅ Socket.IO script loaded successfully<br>";
    echo "Script size: " . strlen($result) . " bytes<br>";
} else {
    echo "❌ Failed to load Socket.IO script<br>";
}

// Method 3: Check if Node.js is running
echo "<h3>Node.js Process Check</h3>";
$processes = [];
if (PHP_OS_FAMILY === 'Windows') {
    $output = shell_exec('tasklist /FI "IMAGENAME eq node.exe" 2>NUL');
    if (strpos($output, 'node.exe') !== false) {
        echo "✅ Node.js process found<br>";
    } else {
        echo "❌ No Node.js process found<br>";
    }
} else {
    $output = shell_exec('ps aux | grep node 2>/dev/null');
    if (strpos($output, 'node') !== false) {
        echo "✅ Node.js process found<br>";
    } else {
        echo "❌ No Node.js process found<br>";
    }
}

// Instructions
echo "<h3>Instructions</h3>";
echo "<p>If Socket.IO server is not running:</p>";
echo "<ol>";
echo "<li>Open terminal/command prompt</li>";
echo "<li>Navigate to project directory: <code>cd " . __DIR__ . "</code></li>";
echo "<li>Run: <code>node socket-server.js</code></li>";
echo "<li>Or run: <code>npm start</code></li>";
echo "</ol>";

echo "<h3>Alternative: Use CDN</h3>";
echo "<p>If you can't run Socket.IO server, you can use CDN version:</p>";
echo "<pre>&lt;script src=\"https://cdn.socket.io/4.7.2/socket.io.min.js\"&gt;&lt;/script&gt;</pre>";
echo "<p>But this won't work for real-time features without a server.</p>";
?>
