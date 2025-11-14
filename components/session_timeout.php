<?php
// Session timeout check for logged-in users
// Timeout after 1 hour of inactivity (3600 seconds)

if(isset($_SESSION['user_id'])) {
    $timeout_duration = 3600; // 1 hour in seconds
    
    // Check if last activity is set
    if(isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        // If elapsed time exceeds timeout duration, log out
        if($elapsed_time > $timeout_duration) {
            // Store username before destroying session
            $user_name = $_SESSION['user_name'] ?? 'User';
            
            // Clear session
            $_SESSION = array();
            
            // Destroy session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            
            // Start new session for timeout message
            session_start();
            $_SESSION['timeout_message'] = ' Please log in again.';
            
            // Redirect to login page
            header('location: user_login.php');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}
?>
