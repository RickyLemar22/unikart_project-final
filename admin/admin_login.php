<?php
include '../components/connect.php';
session_start();

// Redirect if already logged in
if(isset($_SESSION['admin_id'])){
    header('location:dashboard.php');
    exit;
}

$message = [];

if(isset($_POST['submit'])){
    // Sanitize input
    $username = trim($_POST['username'] ?? ''); // Changed from 'name' to 'username'
    $pass = trim($_POST['pass'] ?? '');

    if($username === '' || $pass === ''){
        $message[] = 'Please enter both username and password!';
    } else {
        // CORRECTED: Using 'username' column instead of 'name'
        $select_admin = $conn->prepare("SELECT * FROM `admins` WHERE username = ? AND is_active = 1");
        $select_admin->execute([$username]);

        if($select_admin->rowCount() > 0){
            $admin = $select_admin->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if(password_verify($pass, $admin['password'])){
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$admin['id']]);
                
                header('location:dashboard.php');
                exit;
            } else {
                $message[] = 'Incorrect username or password!';
            }
        } else {
            $message[] = 'Incorrect username or password!';
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
<title>Admin Login | Unikart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<section class="form-container">
    <form action="" method="post">
        <h3> Unikart</h3>

        <?php
        if(!empty($message)){
            foreach($message as $msg){
                echo '<div class="message"><span>'.htmlspecialchars($msg).'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
            }
        }
        ?>

        <!-- Changed name attribute from "name" to "username" -->
        <input type="text" name="username" required placeholder="Enter username" maxlength="20" class="box" oninput="this.value=this.value.replace(/\s/g,'')">
        <input type="password" name="pass" required placeholder="Enter password" maxlength="20" class="box" oninput="this.value=this.value.replace(/\s/g,'')">
        <input type="submit" name="submit" value="Login Now" class="btn">
    </form>
</section>

</body>
</html>