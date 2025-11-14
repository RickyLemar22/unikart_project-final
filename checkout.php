<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   header('location:user_login.php');
   exit;
}

// Initialize variables
$name = $number = $email = $method = $address = '';
$total_products = '';
$grand_total = 0;
$cart_items = [];

// Fetch user details if available
$select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_user->execute([$user_id]);
$user_data = $select_user->fetch(PDO::FETCH_ASSOC);

if($user_data){
   $name = $user_data['name'] ?? '';
   $email = $user_data['email'] ?? '';
   $number = $user_data['number'] ?? '';
}

// Fetch cart items
$select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
$select_cart->execute([$user_id]);
$cart_count = $select_cart->rowCount();

if(isset($_POST['order'])){

   // Validation and sanitization
   $name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
   $number = filter_var($_POST['number'], FILTER_SANITIZE_SPECIAL_CHARS);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   $method = filter_var($_POST['method'], FILTER_SANITIZE_SPECIAL_CHARS);
   
   // Address components
   $flat = filter_var($_POST['flat'], FILTER_SANITIZE_SPECIAL_CHARS);
   $street = filter_var($_POST['street'], FILTER_SANITIZE_SPECIAL_CHARS);
   $city = filter_var($_POST['city'], FILTER_SANITIZE_SPECIAL_CHARS);
   $state = filter_var($_POST['state'], FILTER_SANITIZE_SPECIAL_CHARS);
   $country = filter_var($_POST['country'], FILTER_SANITIZE_SPECIAL_CHARS);
   $pin_code = filter_var($_POST['pin_code'], FILTER_SANITIZE_SPECIAL_CHARS);
   
   $address = "Flat no. $flat, $street, $city, $state, $country - $pin_code";
   $total_products = $_POST['total_products'];
   $total_price = $_POST['total_price'];

   // Additional validation
   $errors = [];
   
   if(empty($name) || strlen($name) < 2) {
      $errors[] = 'Please enter a valid name (minimum 2 characters)';
   }
   
   if(empty($number) || !preg_match('/^[0-9]{10,15}$/', $number)) {
      $errors[] = 'Please enter a valid phone number (10-15 digits)';
   }
   
   if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Please enter a valid email address';
   }
   
   if(empty($flat) || empty($street) || empty($city) || empty($state) || empty($country)) {
      $errors[] = 'Please fill in all address fields';
   }
   
   if(empty($pin_code) || !preg_match('/^[0-9]{4,10}$/', $pin_code)) {
      $errors[] = 'Please enter a valid postal code';
   }

   if($cart_count == 0){
      $errors[] = 'Your cart is empty!';
   }

   if(empty($errors)){
      try {
         $conn->beginTransaction();

         // Insert order
         $insert_order = $conn->prepare("INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price) VALUES (?,?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price]);
         
         $order_id = $conn->lastInsertId();

         // Move cart items to order items (if you have an order_items table)
         $select_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart_items->execute([$user_id]);
         
         while($cart_item = $select_cart_items->fetch(PDO::FETCH_ASSOC)){
            // Insert into order_items if table exists
            $insert_order_item = $conn->prepare("INSERT INTO `order_items` (order_id, product_id, name, price, quantity) VALUES (?,?,?,?,?)");
            $insert_order_item->execute([$order_id, $cart_item['pid'], $cart_item['name'], $cart_item['price'], $cart_item['quantity']]);
            
            // Update product stock if stock management is implemented
            $update_stock = $conn->prepare("UPDATE `products` SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $update_stock->execute([$cart_item['quantity'], $cart_item['pid'], $cart_item['quantity']]);
         }

         // Clear cart
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);

         $conn->commit();
         
         // Update user information
         $update_user = $conn->prepare("UPDATE `users` SET name = ?, number = ?, email = ? WHERE id = ?");
         $update_user->execute([$name, $number, $email, $user_id]);
         
         $message[] = 'Order placed successfully! Order ID: #' . $order_id;
         
         // Redirect to order confirmation page
         header('location: order_confirmation.php?order_id=' . $order_id);
         exit;
         
      } catch (PDOException $e) {
         $conn->rollBack();
         $message[] = 'Error placing order: ' . $e->getMessage();
      }
   } else {
      foreach($errors as $error){
         $message[] = $error;
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
   <title>Unikart - Checkout</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/checkout.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="checkout-orders">

   <div class="checkout-progress">
      <div class="progress-step completed">1. Cart</div>
      <div class="progress-step active">2. Checkout</div>
      <div class="progress-step">3. Confirmation</div>
   </div>

   <form action="" method="POST" id="checkout-form">

      <div class="checkout-flex">
         
         <div class="checkout-section">
            <h3>Delivery Information</h3>
            
            <div class="inputBox">
               <span>Full Name:</span>
               <input type="text" name="name" placeholder="Enter your full name" class="box" maxlength="50" required value="<?= htmlspecialchars($name) ?>">
            </div>
            
            <div class="inputBox">
               <span>Phone Number:</span>
               <input type="tel" name="number" placeholder="Enter your phone number" class="box" pattern="[0-9]{10,15}" required value="<?= htmlspecialchars($number) ?>">
               <small>Format: 10-15 digits</small>
            </div>
            
            <div class="inputBox">
               <span>Email Address:</span>
               <input type="email" name="email" placeholder="Enter your email" class="box" maxlength="50" required value="<?= htmlspecialchars($email) ?>">
            </div>

            <h3>Delivery Address</h3>
            
            <div class="form-flex">
               <div class="inputBox">
                  <span>Flat/House No:</span>
                  <input type="text" name="flat" placeholder="Flat/House number" class="box" maxlength="50" required>
               </div>
               <div class="inputBox">
                  <span>Street Name:</span>
                  <input type="text" name="street" placeholder="Street name" class="box" maxlength="50" required>
               </div>
            </div>
            
            <div class="form-flex">
               <div class="inputBox">
                  <span>City:</span>
                  <input type="text" name="city" placeholder="City" class="box" maxlength="50" required>
               </div>
               <div class="inputBox">
                  <span>State/Region:</span>
                  <input type="text" name="state" placeholder="State/Region" class="box" maxlength="50" required>
               </div>
            </div>
            
            <div class="form-flex">
               <div class="inputBox">
                  <span>Country:</span>
                  <input type="text" name="country" placeholder="Country" class="box" maxlength="50" required value="Uganda">
               </div>
               <div class="inputBox">
                  <span>Postal Code:</span>
                  <input type="text" name="pin_code" placeholder="e.g. 123456" class="box" pattern="[0-9]{4,10}" required>
               </div>
            </div>
            
            <h3>Payment Method</h3>
            <div class="payment-methods">
               <div class="payment-method" data-method="cash on delivery">
                  <div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div>
                  <div>Cash on Delivery</div>
               </div>
               <div class="payment-method" data-method="mobile money">
                  <div class="payment-icon"><i class="fas fa-mobile-alt"></i></div>
                  <div>Mobile Money</div>
               </div>
               <div class="payment-method" data-method="credit card">
                  <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
                  <div>Credit Card</div>
               </div>
            </div>
            <input type="hidden" name="method" id="payment-method" value="cash on delivery" required>
         </div>

         <div class="order-summary">
            <h3>Order Summary</h3>
            
            <?php
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            $product_names = [];

            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  $product_names[] = $fetch_cart['name'].' ('.$fetch_cart['quantity'].' x UGX '.number_format($fetch_cart['price']).')';
                  $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
            ?>
               <div class="order-item">
                  <div class="item-details">
                     <strong><?= $fetch_cart['name']; ?></strong>
                     <div class="item-quantity">Quantity: <?= $fetch_cart['quantity']; ?></div>
                  </div>
                  <div class="item-price">
                     UGX <?= number_format($fetch_cart['price'] * $fetch_cart['quantity']); ?>
                  </div>
               </div>
            <?php
               }
               $total_products = implode(', ', $product_names);
            }else{
               echo '<p class="empty">Your cart is empty!</p>';
            }
            ?>
            
            <div class="order-totals">
               <div class="total-line">
                  <span>Subtotal:</span>
                  <span>UGX <?= number_format($grand_total); ?></span>
               </div>
               <div class="total-line">
                  <span>Delivery Fee:</span>
                  <span>UGX 0</span>
               </div>
               <div class="total-line grand-total">
                  <span>Total:</span>
                  <span>UGX <?= number_format($grand_total); ?></span>
               </div>
            </div>
            
            <input type="hidden" name="total_products" value="<?= $total_products; ?>">
            <input type="hidden" name="total_price" value="<?= $grand_total; ?>">
            
            <input type="submit" name="order" class="btn <?= ($grand_total > 1 && $cart_count > 0)?'':'disabled'; ?>" value="Place Order" <?= ($grand_total > 1 && $cart_count > 0)?'':'disabled'; ?>>
            
            <div class="security-notice">
               <p><i class="fas fa-lock"></i> Your payment information is secure and encrypted</p>
            </div>
         </div>
         
      </div>

   </form>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/checkout.js"></script>

</body>
</html>