<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['send'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $number = $_POST['number'];
   $number = filter_var($number, FILTER_SANITIZE_STRING);
   $msg = $_POST['msg'];
   $msg = filter_var($msg, FILTER_SANITIZE_STRING);

   $select_message = $conn->prepare("SELECT * FROM `messages` WHERE name = ? AND email = ? AND number = ? AND message = ?");
   $select_message->execute([$name, $email, $number, $msg]);

   if($select_message->rowCount() > 0){
      $message[] = 'Message already sent!';
   }else{
      $insert_message = $conn->prepare("INSERT INTO `messages`(user_id, name, email, number, message) VALUES(?,?,?,?,?)");
      $insert_message->execute([$user_id, $name, $email, $number, $msg]);
      $message[] = 'Message sent successfully!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Contact Us - UniKart</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/guest_user_header.php'; ?>

<section class="modern-contact">
   <div class="contact-container">
      <div class="contact-header">
         <h2>Contact Us</h2>
         <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
      </div>

      <form action="" method="post" class="modern-contact-form">
         <div class="form-row">
            <div class="form-group">
               <label for="name">Full Name</label>
               <input type="text" name="name" id="name" placeholder="Enter your full name" required maxlength="20" class="form-input">
            </div>
            <div class="form-group">
               <label for="email">Email Address</label>
               <input type="email" name="email" id="email" placeholder="Enter your email" required maxlength="50" class="form-input">
            </div>
         </div>
         
         <div class="form-group">
            <label for="number">Phone Number</label>
            <input type="number" name="number" id="number" min="0" max="9999999999" placeholder="Enter your phone number" required onkeypress="if(this.value.length == 10) return false;" class="form-input">
         </div>
         
         <div class="form-group">
            <label for="msg">Your Message</label>
            <textarea name="msg" id="msg" class="form-textarea" placeholder="Tell us how we can help you..." cols="30" rows="6" required></textarea>
         </div>
         
         <button type="submit" name="send" class="submit-btn">
            <i class="fas fa-paper-plane"></i> Send Message
         </button>
      </form>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>