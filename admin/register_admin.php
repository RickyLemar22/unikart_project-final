<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if(!$admin_id){
    header('location:admin_login.php');
    exit;
}

$message = [];

if(isset($_POST['submit'])){
    // Sanitize and validate inputs
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $role = $_POST['role'] ?? 'admin';
    $pass = $_POST['pass'] ?? '';
    $cpass = $_POST['cpass'] ?? '';
    
    // Validation
    if(!$username || !$full_name || !$email || !$pass || !$cpass){
        $message[] = 'All fields are required!';
    } elseif(!$email){
        $message[] = 'Invalid email address!';
    } elseif($pass !== $cpass){
        $message[] = 'Passwords do not match!';
    } elseif(strlen($pass) < 6){
        $message[] = 'Password must be at least 6 characters long!';
    } else {
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT * FROM `admins` WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0){
            $message[] = 'Username or email already exists!';
        } else {
            // Hash password
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            
            // Insert new admin
            $insert_admin = $conn->prepare("
                INSERT INTO `admins` 
                (username, full_name, email, password, role, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            $insert_admin->execute([$username, $full_name, $email, $hashed_pass, $role]);
            
            $message[] = 'New admin registered successfully!';
            
            // Clear form by redirecting
            header('location:register_admin.php?success=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Admin | Unikart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
<style>
    .form-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    
    .form-container form {
        background: white;
        padding: 3rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        max-width: 500px;
        width: 100%;
    }
    
    .form-container form h3 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 2rem;
        text-align: center;
        text-transform: uppercase;
    }
    
    .box {
        width: 100%;
        padding: 1.2rem;
        margin: 1rem 0;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .box:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        font-size: 1.4rem;
        color: #555;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .password-requirements {
        font-size: 1.2rem;
        color: #666;
        margin-top: 0.3rem;
        display: block;
    }
    
    .success-message {
        background: #27ae60;
        color: white;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="form-container">
    <form action="" method="post">
        <h3>Register New Admin</h3>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Admin registered successfully!
            </div>
        <?php endif; ?>
        
        <?php
        if(!empty($message)){
            foreach($message as $msg){
                echo '<div class="message"><span>'.htmlspecialchars($msg).'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
            }
        }
        ?>
        
        <div class="form-group">
            <label for="username">Username <span style="color: red;">*</span></label>
            <input type="text" 
                   id="username"
                   name="username" 
                   required 
                   placeholder="Enter username (no spaces)" 
                   minlength="3"
                   maxlength="50" 
                   class="box" 
                   oninput="this.value=this.value.replace(/\s/g,'')"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="full_name">Full Name <span style="color: red;">*</span></label>
            <input type="text" 
                   id="full_name"
                   name="full_name" 
                   required 
                   placeholder="Enter full name" 
                   maxlength="100" 
                   class="box"
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email Address <span style="color: red;">*</span></label>
            <input type="email" 
                   id="email"
                   name="email" 
                   required 
                   placeholder="Enter email address" 
                   maxlength="100"
                   class="box"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="role">Role <span style="color: red;">*</span></label>
            <select name="role" id="role" required class="box">
                <option value="admin" selected>Admin</option>
                <option value="moderator">Moderator</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="pass">Password <span style="color: red;">*</span></label>
            <input type="password" 
                   id="pass"
                   name="pass" 
                   required 
                   placeholder="Enter password" 
                   minlength="6"
                   maxlength="50" 
                   class="box">
        </div>
        
        <div class="form-group">
            <label for="cpass">Confirm Password <span style="color: red;">*</span></label>
            <input type="password" 
                   id="cpass"
                   name="cpass" 
                   required 
                   placeholder="Confirm password" 
                   minlength="6"
                   maxlength="50" 
                   class="box">
        </div>
        
        <input type="submit" value="Register Admin" class="btn" name="submit">
        
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="admins_accounts.php" style="color: #667eea; text-decoration: none; font-size: 1.4rem;">
                <i class="fas fa-arrow-left"></i> Back to Admins List
            </a>
        </div>
    </form>
</section>

<script src="../js/admin_script.js"></script>
<script>
    // Password confirmation validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const pass = document.getElementById('pass').value;
        const cpass = document.getElementById('cpass').value;
        
        if(pass !== cpass) {
            e.preventDefault();
            alert('Passwords do not match!');
            document.getElementById('cpass').focus();
        }
    });
    
    // Real-time password match indicator
    document.getElementById('cpass').addEventListener('input', function() {
        const pass = document.getElementById('pass').value;
        const cpass = this.value;
        
        if(cpass.length > 0) {
            if(pass === cpass) {
                this.style.borderColor = '#27ae60';
            } else {
                this.style.borderColor = '#e74c3c';
            }
        } else {
            this.style.borderColor = '#e0e0e0';
        }
    });
</script>
</body>
</html>