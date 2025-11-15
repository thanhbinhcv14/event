<?php
session_start();

// Set user offline before logout
if (isset($_SESSION['user']['ID_User'])) {
    try {
        require_once 'config/database.php';
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET OnlineStatus = 'Offline' WHERE ID_User = ?");
        $stmt->execute([$_SESSION['user']['ID_User']]);
        error_log("User " . $_SESSION['user']['ID_User'] . " set offline on logout");
    } catch (Exception $e) {
        error_log("Error setting user offline: " . $e->getMessage());
    }
}

session_unset();
session_destroy();

// Clear chat history on logout
// This will be handled by JavaScript before redirect
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang đăng xuất...</title>
</head>
<body>
    <script>
        // Clear chat history from localStorage before redirect
        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem('geminiChatHistory');
            console.log('Chat history cleared: User logged out');
        }
        // Redirect to login page
        window.location.href = 'login.php';
    </script>
</body>
</html>
<?php exit; ?>