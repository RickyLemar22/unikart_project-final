<?php
include 'components/connect.php';
session_start();

if(!isset($_SESSION['user_id'])){
   header('location:user_login.php');
   exit;
}
$user_id = $_SESSION['user_id'];

// Fetch current user info from student table
$select_profile = $conn->prepare("SELECT * FROM `student` WHERE student_id = ?");
$select_profile->execute([$user_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

if(isset($_POST['submit'])){

   // Sanitize inputs
   $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
   $university_email = filter_var($_POST['university_email'], FILTER_SANITIZE_EMAIL);
   $contact = filter_var($_POST['contact'], FILTER_SANITIZE_STRING);
   $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
   $faculty = filter_var($_POST['faculty'], FILTER_SANITIZE_STRING);
   $year_of_study = filter_var($_POST['year_of_study'], FILTER_SANITIZE_STRING);

   // Update profile information
   $update_profile = $conn->prepare("
      UPDATE `student` 
      SET full_name = ?, university_email = ?, contact = ?, address = ?, faculty = ?, year_of_study = ? 
      WHERE student_id = ?
   ");
   $update_profile->execute([$full_name, $university_email, $contact, $address, $faculty, $year_of_study, $user_id]);

   // Also update user_account table if needed
   $update_account = $conn->prepare("
      UPDATE `user_account` 
      SET university_email = ?, contact = ? 
      WHERE student_id = ?
   ");
   $update_account->execute([$university_email, $contact, $user_id]);

   // Handle password update
   $old_pass_input = $_POST['old_pass'];
   $new_pass_input = $_POST['new_pass'];
   $cpass_input = $_POST['cpass'];

   if(!empty($old_pass_input) || !empty($new_pass_input) || !empty($cpass_input)){

      // Verify old password
      if(!password_verify($old_pass_input, $fetch_profile['password'])){
         $message[] = 'Old password is incorrect!';
      } elseif($new_pass_input !== $cpass_input){
         $message[] = 'New password and confirm password do not match!';
      } else {
         // Hash new password and update both tables
         $new_hashed_pass = password_hash($new_pass_input, PASSWORD_DEFAULT);
         
         // Update student table
         $update_pass_student = $conn->prepare("UPDATE `student` SET password = ? WHERE student_id = ?");
         $update_pass_student->execute([$new_hashed_pass, $user_id]);
         
         // Update user_account table
         $update_pass_account = $conn->prepare("UPDATE `user_account` SET password = ? WHERE student_id = ?");
         $update_pass_account->execute([$new_hashed_pass, $user_id]);
         
         $message[] = 'Password updated successfully!';
      }
   } else {
      $message[] = 'Profile updated successfully!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile - UniKart</title>

   <!-- font awesome cdn link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="modern-register">
   <div class="register-container">
      <div class="register-header">
         <h1>Update Profile</h1>
         <p>Update your account information</p>
      </div>

      <form action="" method="post" class="register-form">
         <?php if(isset($message)): ?>
            <?php foreach($message as $msg): ?>
               <div class="register-error">
                  <i class="fas fa-exclamation-circle"></i> <?= $msg ?>
               </div>
            <?php endforeach; ?>
         <?php endif; ?>

         <div class="form-group">
            <label class="form-label">Full Name</label>
            <div class="input-wrapper">
               <i class="fas fa-user input-icon"></i>
               <input type="text" name="full_name" required placeholder="Enter your full name" 
                      maxlength="100" class="form-input with-icon" 
                      value="<?= htmlspecialchars($fetch_profile['full_name'] ?? ''); ?>">
            </div>
         </div>

         <div class="form-group">
            <label class="form-label">University Email</label>
            <div class="input-wrapper">
               <i class="fas fa-envelope input-icon"></i>
               <input type="email" name="university_email" required placeholder="Enter your university email" 
                      maxlength="100" class="form-input with-icon" 
                      value="<?= htmlspecialchars($fetch_profile['university_email'] ?? ''); ?>">
            </div>
         </div>

         <div class="form-group">
            <label class="form-label">Phone Number</label>
            <div class="input-wrapper">
               <i class="fas fa-phone input-icon"></i>
               <input type="text" name="contact" required placeholder="Enter your phone number" 
                      maxlength="15" class="form-input with-icon" 
                      value="<?= htmlspecialchars($fetch_profile['contact'] ?? ''); ?>">
            </div>
         </div>

         <div class="form-group">
            <label class="form-label">Address</label>
            <div class="input-wrapper">
               <i class="fas fa-home input-icon"></i>
               <input type="text" name="address" required placeholder="Enter your address" 
                      maxlength="255" class="form-input with-icon" 
                      value="<?= htmlspecialchars($fetch_profile['address'] ?? ''); ?>">
            </div>
         </div>

         <div class="form-row">
            <div class="form-group">
               <label class="form-label">Faculty/College</label>
               <div class="input-wrapper">
                  <i class="fas fa-university input-icon"></i>
                  <input type="text" name="faculty" required placeholder="Enter your faculty" 
                         maxlength="100" class="form-input with-icon" 
                         value="<?= htmlspecialchars($fetch_profile['faculty'] ?? ''); ?>">
               </div>
            </div>

            <div class="form-group">
               <label class="form-label">Year of Study</label>
               <div class="input-wrapper">
                  <i class="fas fa-graduation-cap input-icon"></i>
                  <input type="text" name="year_of_study" required placeholder="e.g., Year 2" 
                         maxlength="20" class="form-input with-icon" 
                         value="<?= htmlspecialchars($fetch_profile['year_of_study'] ?? ''); ?>">
               </div>
            </div>
         </div>

         <div class="form-group">
            <label class="form-label">Old Password (leave blank to keep current)</label>
            <div class="input-wrapper">
               <i class="fas fa-lock input-icon"></i>
               <input type="password" name="old_pass" placeholder="Enter old password" 
                      maxlength="50" class="form-input with-icon">
            </div>
         </div>

         <div class="form-row">
            <div class="form-group">
               <label class="form-label">New Password</label>
               <div class="input-wrapper">
                  <i class="fas fa-lock input-icon"></i>
                  <input type="password" name="new_pass" placeholder="Enter new password" 
                         maxlength="50" class="form-input with-icon">
               </div>
            </div>

            <div class="form-group">
               <label class="form-label">Confirm New Password</label>
               <div class="input-wrapper">
                  <i class="fas fa-lock input-icon"></i>
                  <input type="password" name="cpass" placeholder="Confirm new password" 
                         maxlength="50" class="form-input with-icon">
               </div>
            </div>
         </div>

         <button type="submit" name="submit" class="register-btn">
            <span class="btn-text">Update Profile</span>
            <i class="fas fa-user-edit"></i>
         </button>
      </form>

      <div class="register-footer">
         <p>Need to make changes? Contact support if you need help.</p>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });

    // Input validation - remove spaces
    const inputs = document.querySelectorAll('input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });
    });

    // Email validation
    const emailInput = document.querySelector('input[name="university_email"]');
    emailInput.addEventListener('input', function() {
        this.value = this.value.replace(/\s/g, '');
    });
});
</script>

</body>
</html>