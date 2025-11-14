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
    // Sanitize input
    $full_name = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
    $university_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $contact = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
    $faculty = htmlspecialchars(trim($_POST['faculty']), ENT_QUOTES, 'UTF-8');
    $year_of_study = intval($_POST['year']); // Convert to integer
    $pass = $_POST['pass'];
    $cpass = $_POST['cpass'];

    // Validation
    if(empty($full_name) || empty($university_email) || empty($contact) || empty($address) || empty($faculty) || empty($year_of_study) || empty($pass)) {
        $message[] = 'All fields are required!';
    } elseif(!filter_var($university_email, FILTER_VALIDATE_EMAIL)) {
        $message[] = 'Please enter a valid email address!';
    } elseif($pass != $cpass) {
        $message[] = 'Confirm password does not match!';
    } elseif(strlen($pass) < 6) {
        $message[] = 'Password must be at least 6 characters long!';
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT student_id FROM student WHERE university_email = ?");
        $check_email->execute([$university_email]);
        
        // Check if contact already exists
        $check_contact = $conn->prepare("SELECT student_id FROM student WHERE contact = ?");
        $check_contact->execute([$contact]);
        
        if($check_email->rowCount() > 0) {
            $message[] = 'This email is already registered. Please login or use a different email.';
        } elseif($check_contact->rowCount() > 0) {
            $message[] = 'This phone number is already registered. Please use a different phone number.';
        } else {
            try {
                // Hash password
                $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                
                // Insert into student table
                $insert_student = $conn->prepare("
                    INSERT INTO student 
                    (full_name, university_email, contact, address, faculty, year_of_study, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_student->execute([
                    $full_name, $university_email, $contact, $address, $faculty, $year_of_study, $hashed_password
                ]);
                
                $student_id = $conn->lastInsertId();
                
                // Insert into user_account table (only university_email)
                $insert_account = $conn->prepare("
                    INSERT INTO user_account 
                    (university_email) 
                    VALUES (?)
                ");
                
                $insert_account->execute([$university_email]);
                
                // Set session and redirect
                $_SESSION['user_id'] = $student_id;
                $_SESSION['user_email'] = $university_email;
                $_SESSION['user_name'] = $full_name;
                
                $_SESSION['success_message'] = 'Registration successful! Welcome to UniKart.';
                header('location:home.php');
                exit;
                
            } catch (PDOException $e) {
                // Convert technical errors to user-friendly messages
                $error_code = $e->getCode();
                $error_message = $e->getMessage();
                
                if($error_code == 23000 || strpos($error_message, 'Duplicate entry') !== false) {
                    if(strpos($error_message, 'university_email') !== false) {
                        $message[] = 'This email address is already registered. Please login or use a different email.';
                    } elseif(strpos($error_message, 'contact') !== false) {
                        $message[] = 'This phone number is already registered. Please use a different phone number.';
                    } else {
                        $message[] = 'An account with this information already exists. Please check your details.';
                    }
                } else {
                    $message[] = 'Registration failed. Please try again or contact support if the problem persists.';
                    // Log the actual error for developers (optional)
                    error_log("Registration error: " . $e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniKart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-toggle {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light-color);
            font-size: 1.4rem;
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0.5rem;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--main-color);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .form-input.with-toggle {
            padding-right: 4.5rem;
        }
        
        .register-error {
            background: #fee;
            color: #e74c3c;
            padding: 1.2rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.3s ease;
            border-left: 4px solid #e74c3c;
        }
        
        .register-error i {
            font-size: 1.6rem;
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
        
        .helper-text {
            font-size: 1.2rem;
            color: #666;
            display: block;
            margin-top: 0.5rem;
        }
        
        .helper-text i {
            margin-right: 0.3rem;
        }
    </style>
</head>
<body>

<?php include 'components/guest_user_header.php'; ?>

<section class="modern-register">
    <div class="register-container">
        <div class="register-header">
            <h1>Join UniKart</h1>
            <p>And do your campus shopping at student-friendly prices.</p>
        </div>

        <form action="" method="post" class="register-form">
            <?php if(!empty($message)): ?>
                <?php foreach($message as $msg): ?>
                    <div class="register-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($msg) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Full Name <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="fullname" required placeholder="Enter your full name" 
                           maxlength="100" class="form-input with-icon"
                           value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">University Email <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" required placeholder="Enter your university email" 
                           maxlength="100" class="form-input with-icon"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <small class="helper-text">
                    <i class="fas fa-info-circle"></i> Use your official university email address
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="tel" name="phone" required placeholder="Enter your phone number" 
                           maxlength="15" class="form-input with-icon" 
                           pattern="[0-9]{10,15}"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <small class="helper-text">
                    <i class="fas fa-info-circle"></i> 10-15 digits only (e.g., 0701234567)
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Address <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input type="text" name="address" required placeholder="Hostel/Hall, Room No." 
                           maxlength="255" class="form-input with-icon" 
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>
                <small class="helper-text">
                    <i class="fas fa-info-circle"></i> Where you'd like your orders delivered
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Faculty/College <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-building input-icon"></i>
                    <input type="text" name="faculty" required placeholder="Enter your Faculty/College" 
                           maxlength="100" class="form-input with-icon" 
                           value="<?= htmlspecialchars($_POST['faculty'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Year of Study <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar input-icon"></i>
                    <select name="year" required class="form-input with-icon">
                        <option value="" disabled selected>Select your year</option>
                        <option value="1" <?= (isset($_POST['year']) && $_POST['year'] == '1') ? 'selected' : '' ?>>Year 1</option>
                        <option value="2" <?= (isset($_POST['year']) && $_POST['year'] == '2') ? 'selected' : '' ?>>Year 2</option>
                        <option value="3" <?= (isset($_POST['year']) && $_POST['year'] == '3') ? 'selected' : '' ?>>Year 3</option>
                        <option value="4" <?= (isset($_POST['year']) && $_POST['year'] == '4') ? 'selected' : '' ?>>Year 4</option>
                        <option value="5" <?= (isset($_POST['year']) && $_POST['year'] == '5') ? 'selected' : '' ?>>Year 5</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="pass" required 
                           placeholder="Enter password (min. 6 characters)" 
                           minlength="6" maxlength="50" class="form-input with-icon with-toggle">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="helper-text">
                    <i class="fas fa-info-circle"></i> Minimum 6 characters
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password <span style="color: red;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm-password" name="cpass" required 
                           placeholder="Confirm your password" 
                           maxlength="50" class="form-input with-icon with-toggle">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm-password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="submit" class="register-btn">
                <span class="btn-text">Create Account</span>
                <i class="fas fa-user-plus"></i>
            </button>
        </form>

        <div class="register-footer">
            <p>Already have an account?</p>
            <a href="user_login.php" class="login-link">
                <i class="fas fa-sign-in-alt"></i> Login Now
            </a>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Real-time password match validation
document.getElementById('confirm-password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            this.style.borderColor = '#27ae60';
        } else {
            this.style.borderColor = '#e74c3c';
        }
    } else {
        this.style.borderColor = '';
    }
});

// Form validation before submit
document.querySelector('.register-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm-password').focus();
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        document.getElementById('password').focus();
        return false;
    }
});

// Phone number validation - only digits
document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
});
</script>

</body>
</html>
