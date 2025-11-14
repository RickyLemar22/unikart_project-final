<?php
session_start();

// If user is not logged in, redirect to home
if(!isset($_SESSION['user_id'])) {
    header('location: home.php');
    exit;
}

// Check if form was submitted (POST request)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    // Store user name for success message
   
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start new session for success message
    session_start();
    $_SESSION['success_message'] = "Successfully logged out.";
    
    // Redirect to home page
    header('location: home.php');
    exit;
}

// Show confirmation form
//$user_name = $_SESSION['user_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation - UniKart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="logout-page">
    <div class="logout-confirmation">
        <div class="confirmation-container">
            <div class="confirmation-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            
            <form method="post" class="confirmation-form">
                <div class="button-group">
                    <a href="home.php" class="btn cancel-btn">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" name="confirm_logout" class="btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Yes, Logout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.logout-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.confirmation-container {
    background: white;
    padding: 3rem;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    text-align: center;
    max-width: 500px;
    width: 100%;
}

.confirmation-icon {
    font-size: 4rem;
    color: #e74c3c;
    margin-bottom: 1.5rem;
}

.confirmation-container h2 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 2rem;
}

.confirmation-container p {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.button-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
}

.cancel-btn {
    background: #95a5a6;
    color: white;
}

.cancel-btn:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.logout-btn {
    background: #e74c3c;
    color: white;
}

.logout-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .confirmation-container {
        padding: 2rem;
        margin: 1rem;
    }
    
    .button-group {
        flex-direction: column;
    }
}
</style>

</body>
</html>