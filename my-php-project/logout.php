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
header('Location: login.php');
exit;
?>