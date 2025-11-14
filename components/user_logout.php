<?php
// Start session first
session_start();

// Include database connection if needed for any cleanup
include 'connect.php';

// Clear all session variables
$_SESSION = array();

// To kill the session and also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Redirect to home page
header('Location: ../home.php?logout=success');
exit;
?>