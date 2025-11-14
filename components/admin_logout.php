<?php
// Start session first
session_start();

// Include database connection if needed for any cleanup
include 'connect.php';

// Store logout message before clearing session
$logout_message = 'You have been logged out successfully.';

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
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

// Start a new session just for the logout message
session_start();
$_SESSION['logout_success'] = $logout_message;

// Redirect to admin login page
header('Location: ../admin/admin_login.php');
exit;
?>