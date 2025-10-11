<?php
// require_once __DIR__ . '/../../config/config.php';
// use Firebase\JWT\JWT;
// use Firebase\JWT\Key;

// function authenticate() {
//     $headers = getallheaders();
//     if (!isset($headers['Authorization'])) {
//         http_response_code(401);
//         echo json_encode(['error' => 'Missing Authorization header']);
//         exit;
//     }
//     $authHeader = $headers['Authorization'];
//     if (strpos($authHeader, 'Bearer ') !== 0) {
//         http_response_code(401);
//         echo json_encode(['error' => 'Invalid Authorization header']);
//         exit;
//     }
//     $jwt = substr($authHeader, 7);
//     try {
//         $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
//         return $decoded;
//     } catch (Exception $e) {
//         http_response_code(401);
//         echo json_encode(['error' => 'Invalid token']);
//         exit;
//     }
// }
?>
<?php
require_once __DIR__ . '/../../config/config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// Check if user is logged in using session
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Check both session structures
    return (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) || 
           (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']));
}

// Get current user ID from session
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug session data
    error_log("Session data: " . print_r($_SESSION, true));
    
    // Check multiple session structures
    if (isset($_SESSION['user']['ID_User']) && !empty($_SESSION['user']['ID_User'])) {
        return $_SESSION['user']['ID_User'];
    }
    if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
        return $_SESSION['user']['id'];
    }
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    error_log("No valid user ID found in session");
    return 0;
}

// Get current user role from session
function getCurrentUserRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Check both session structures
    if (isset($_SESSION['user']['ID_Role'])) {
        return $_SESSION['user']['ID_Role'];
    }
    if (isset($_SESSION['user']['role'])) {
        return $_SESSION['user']['role'];
    }
    return $_SESSION['user_role'] ?? 0;
}

function authenticate() {
    header('Content-Type: application/json');

    $headers = array_change_key_case(getallheaders(), CASE_UPPER);
    if (!isset($headers['AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization header']);
        exit;
    }

    $authHeader = $headers['AUTHORIZATION'];
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Authorization header']);
        exit;
    }

    $jwt = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
        return $decoded;
    } catch (ExpiredException $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token expired']);
        exit;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
}
