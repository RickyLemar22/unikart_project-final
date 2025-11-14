<?php

include 'components/connect.php';

session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id'])){
    header('location:home.php');
    exit;
}

$message = [];

if(isset($_POST['submit'])){
    $login = trim($_POST['login']);
    $pass = $_POST['pass'];

    if(empty($login) || empty($pass)){
        $message[] = 'Please enter both login and password.';
    } else {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        
        if($isEmail){
            // Login with email - check both student and user_account tables
            $select_user = $conn->prepare("
                SELECT ua.*, s.password, s.full_name 
                FROM `user_account` ua 
                LEFT JOIN `student` s ON ua.university_email = s.university_email 
                WHERE ua.university_email = ? AND ua.account_status = 'active'
            ");
        } else {
            // Login with contact number - check student table
            $select_user = $conn->prepare("
                SELECT ua.*, s.password, s.full_name 
                FROM `user_account` ua 
                LEFT JOIN `student` s ON ua.university_email = s.university_email 
                WHERE s.contact = ? AND ua.account_status = 'active'
            ");
        }
        
        $select_user->execute([$login]);
        $row = $select_user->fetch(PDO::FETCH_ASSOC);
        
        if($row){
            // Check if password exists and verify it
            if(!empty($row['password']) && password_verify($pass, $row['password'])){
                // Set session variables FIRST
                $_SESSION['user_id'] = $row['account_id'];
                $_SESSION['user_email'] = $row['university_email'];
                
                // Use student name if available, otherwise use email
                $_SESSION['user_name'] = !empty($row['full_name']) ? $row['full_name'] : $row['university_email'];
                
                // Update last login
                $update_login = $conn->prepare("UPDATE user_account SET last_login = NOW() WHERE account_id = ?");
                $update_login->execute([$row['account_id']]);
                
                $_SESSION['success_message'] = 'Welcome To Unikart';
                // Set success message
                
                // Redirect to home page where header will show user info
                header('location:home.php');
                exit;
            } else {
                $message[] = 'Incorrect password!';
            }
        } else {
            $message[] = 'Account not found or inactive!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniKart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Show guest header for login page -->
<?php 
// For login page, show header without user-specific elements
include 'components/guest_user_header.php'; 
?>

<section class="modern-login">
    <div class="login-container">
        <div class="login-header">
            <h1>Login here to access your Unikart Account</h1>
        </div>

        <form action="" method="post" class="login-form">
            <?php if(!empty($message)): ?>
                <?php foreach($message as $msg): ?>
                    <div class="login-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $msg ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Email or Phone Number</label>
                <div class="input-wrapper">
                    <input type="text" name="login" required placeholder="username@must.ac.ug" 
                           maxlength="100" class="form-input with-icon" 
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="pass" required placeholder="Enter your password" 
                           maxlength="50" class="form-input with-icon" id="passwordInput">
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <a href="forgot_password.php" class="forgot-password">
                    Forgot your password?
                </a>
            </div>

            <button type="submit" name="submit" class="login-btn">
                <span class="btn-text">Login Now</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="login-footer">
            <p>Don't have an account?</p>
            <a href="user_register.php" class="register-link">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('passwordInput');
    const passwordToggle = document.getElementById('passwordToggle');
    
    // Password toggle functionality
    passwordToggle.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    
    // Input validation - remove spaces
    const loginInput = document.querySelector('input[name="login"]');
    loginInput.addEventListener('input', function() {
        this.value = this.value.replace(/\s/g, '');
    });
    
    passwordInput.addEventListener('input', function() {
        this.value = this.value.replace(/\s/g, '');
    });
});
</script>
<!-- In your existing user_login.php - just add this small script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            // Optional: You can add API login as an enhancement
            // But keep your existing PHP login working
            console.log('Form submitted - PHP will handle it');
        });
    }
});
</script>

</body>
</html>