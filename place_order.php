<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

// Check if cart is empty
$check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
$check_cart->execute([$user_id]);
if($check_cart->rowCount() == 0){
   header('location:cart.php');
   exit();
}

// Calculate grand total
$grand_total = 0;
$select_cart = $conn->prepare("SELECT c.*, p.name, p.price as product_price FROM `cart` c 
                              JOIN `products` p ON c.product_id = p.product_id 
                              WHERE c.user_id = ?");
$select_cart->execute([$user_id]);
while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
   $grand_total += ($fetch_cart['product_price'] * $fetch_cart['quantity']);
}

// Process order placement
if(isset($_POST['place_order'])){
   // Get form data
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $delivery_method = filter_var($_POST['delivery_method'], FILTER_SANITIZE_STRING);
   $payment_method = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
   
   // Use user's registered phone number
   $phone = $user['contact'] ?? '';
   
   // System assigns delivery date and time (next day by default)
   $delivery_date = date('Y-m-d', strtotime('+1 day'));
   $delivery_time = '14:00:00'; // Default 2:00 PM
   
   // Additional fields based on delivery method
   if($delivery_method == 'pickup'){
      $pickup_station = filter_var($_POST['pickup_station'], FILTER_SANITIZE_STRING);
      $delivery_address = 'Pickup Station: ' . $pickup_station;
   }else{
      $delivery_address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
      $pickup_station = NULL;
   }
   
   // Additional fields for mobile money
   if($payment_method == 'mobile_money'){
      $mobile_money_provider = filter_var($_POST['mobile_money_provider'], FILTER_SANITIZE_STRING);
      $mobile_money_number = filter_var($_POST['mobile_money_number'], FILTER_SANITIZE_STRING);
   }else{
      $mobile_money_provider = NULL;
      $mobile_money_number = NULL;
   }
   
   // Get account_id from user_account table using user_id (assuming user_id is account_id)
   $account_id = $user_id;
   
   // Insert order into orders table
   $insert_order = $conn->prepare("INSERT INTO `orders` 
                                 (account_id, total_amount, delivery_method, delivery_date, delivery_time, 
                                  pickup_station, delivery_address, payment_method, mobile_money_provider, 
                                  mobile_money_number, order_status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
   $insert_order->execute([$account_id, $grand_total, $delivery_method, $delivery_date, $delivery_time, 
                          $pickup_station, $delivery_address, $payment_method, $mobile_money_provider, 
                          $mobile_money_number]);
   
   // Get the last inserted order ID
   $order_db_id = $conn->lastInsertId();
   
   // Move cart items to order items - using the correct column names from your order_items table
   $select_cart_items = $conn->prepare("SELECT c.*, p.name, p.price as product_price 
                                      FROM `cart` c 
                                      JOIN `products` p ON c.product_id = p.product_id 
                                      WHERE c.user_id = ?");
   $select_cart_items->execute([$user_id]);
   
   while($cart_item = $select_cart_items->fetch(PDO::FETCH_ASSOC)){
      // Insert into order_items table with correct column structure
      $insert_order_item = $conn->prepare("INSERT INTO `order_items` 
                                         (order_id, product_id, quantity, price) 
                                         VALUES (?, ?, ?, ?)");
      $insert_order_item->execute([$order_db_id, $cart_item['product_id'], 
                                 $cart_item['quantity'], $cart_item['product_price']]);
   }
   
   // Clear the cart
   $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
   $delete_cart->execute([$user_id]);
   
   // Send notification
   $message[] = 'Order placed successfully! Your order ID is: ' . $order_db_id;
   
   // Redirect to order confirmation
   $_SESSION['order_success'] = true;
   $_SESSION['order_id'] = $order_db_id;
   header('location:order_success.php');
   exit();
}

// Get user details from student table
try {
    $select_user = $conn->prepare("SELECT * FROM `student` WHERE student_id = ?");
    $select_user->execute([$user_id]);
    $user = $select_user->fetch(PDO::FETCH_ASSOC);
    
    // If student not found, try user_account table
    if(!$user) {
        $select_user = $conn->prepare("SELECT * FROM `user_account` WHERE account_id = ?");
        $select_user->execute([$user_id]);
        $user = $select_user->fetch(PDO::FETCH_ASSOC);
        
        // If we found user in user_account but need student details, try to get student by email
        if($user && isset($user['university_email'])) {
            $select_student = $conn->prepare("SELECT * FROM `student` WHERE university_email = ?");
            $select_student->execute([$user['university_email']]);
            $student = $select_student->fetch(PDO::FETCH_ASSOC);
            if($student) {
                $user = array_merge($user, $student);
            }
        }
    }
} catch (PDOException $e) {
    // If tables don't exist, set empty user
    $user = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Place Order - UniKart</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   
   <style>
      .place-order-container {
         max-width: 1200px;
         margin: 2rem auto;
         padding: 0 20px;
      }
      
      .order-grid {
         display: grid;
         grid-template-columns: 1fr 400px;
         gap: 2rem;
         margin-top: 2rem;
      }
      
      .order-form-section, .order-summary-section {
         background: #fff;
         padding: 2rem;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .section-title {
         font-size: 1.5rem;
         margin-bottom: 1.5rem;
         color: var(--main-color);
         border-bottom: 2px solid var(--main-color);
         padding-bottom: 0.5rem;
      }
      
      .form-group {
         margin-bottom: 1.5rem;
      }
      
      .form-group label {
         display: block;
         margin-bottom: 0.5rem;
         font-weight: 600;
         color: #333;
      }
      
      .form-control {
         width: 100%;
         padding: 12px;
         border: 2px solid #ddd;
         border-radius: 5px;
         font-size: 1rem;
         transition: border-color 0.3s;
      }
      
      .form-control:focus {
         border-color: var(--main-color);
         outline: none;
      }
      
      .delivery-options, .payment-options {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1rem;
         margin-bottom: 1rem;
      }
      
      .option-card {
         border: 2px solid #ddd;
         border-radius: 8px;
         padding: 1rem;
         cursor: pointer;
         transition: all 0.3s;
      }
      
      .option-card.selected {
         border-color: var(--main-color);
         background: #f8f9fa;
      }
      
      .option-card input {
         display: none;
      }
      
      .conditional-field {
         display: none;
         margin-top: 1rem;
      }
      
      .conditional-field.active {
         display: block;
      }
      
      .order-summary-item {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 1rem 0;
         border-bottom: 1px solid #eee;
      }
      
      .order-summary-item:last-child {
         border-bottom: none;
      }
      
      .item-image {
         width: 60px;
         height: 60px;
         object-fit: cover;
         border-radius: 5px;
         margin-right: 1rem;
      }
      
      .item-details {
         flex: 1;
      }
      
      .item-name {
         font-weight: 600;
         margin-bottom: 0.25rem;
      }
      
      .item-price {
         color: #666;
         font-size: 0.9rem;
      }
      
      .grand-total {
         font-size: 1.5rem;
         font-weight: 700;
         color: var(--main-color);
         text-align: center;
         margin: 1.5rem 0;
         padding: 1rem;
         background: #f8f9fa;
         border-radius: 8px;
      }
      
      .place-order-btn {
         width: 100%;
         padding: 15px;
         background: var(--main-color);
         color: white;
         border: none;
         border-radius: 8px;
         font-size: 1.1rem;
         font-weight: 600;
         cursor: pointer;
         transition: background 0.3s;
      }
      
      .place-order-btn:hover {
         background: var(--hover-color);
      }
      
      .pickup-stations {
         display: grid;
         grid-template-columns: 1fr;
         gap: 0.5rem;
      }
      
      .station-card {
         border: 1px solid #ddd;
         border-radius: 5px;
         padding: 1rem;
         cursor: pointer;
      }
      
      .station-card.selected {
         border-color: var(--main-color);
         background: #f8f9fa;
      }
      
      .info-box {
         background: #e7f3ff;
         border: 1px solid #b3d9ff;
         border-radius: 8px;
         padding: 1rem;
         margin-bottom: 1.5rem;
      }
      
      .info-box h4 {
         margin: 0 0 0.5rem 0;
         color: #0066cc;
      }
      
      .info-box p {
         margin: 0;
         color: #666;
         font-size: 0.9rem;
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Display Messages -->
<?php if(isset($message)): ?>
    <?php foreach($message as $msg): ?>
        <div class="success-banner" style="background: var(--info-color);">
            <?= $msg ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<section class="place-order-container">
   <div class="cart-header">
      <h1>Place Your Order</h1>
      <p>Complete your order with delivery and payment details</p>
   </div>

   <div class="order-grid">
      <!-- Order Form -->
      <div class="order-form-section">
         <h2 class="section-title">Order Details</h2>
         
         <!-- Information Box -->
         <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Delivery Information</h4>
            <p>Your order will be delivered tomorrow. We'll contact you at <strong><?= htmlspecialchars($user['contact'] ?? 'your registered number') ?></strong> to confirm the exact time.</p>
         </div>
         
         <form method="POST" action="">
            <!-- Contact Information -->
            <div class="form-group">
               <label for="name">Full Name *</label>
               <input type="text" id="name" name="name" class="form-control" 
                      value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
               <label for="phone">Phone Number *</label>
               <input type="text" id="phone" class="form-control" 
                      value="<?= htmlspecialchars($user['contact'] ?? '') ?>" disabled
                      style="background: #f8f9fa;">
               <small style="color: #666;">We'll use your registered phone number for delivery updates</small>
               <input type="hidden" name="phone" value="<?= htmlspecialchars($user['contact'] ?? '') ?>">
            </div>

            <!-- Delivery Method -->
            <div class="form-group">
               <label>Delivery Method *</label>
               <div class="delivery-options">
                  <label class="option-card">
                     <input type="radio" name="delivery_method" value="delivery" required>
                     <div>
                        <strong>Home Delivery</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                           We deliver to your doorstep
                        </p>
                     </div>
                  </label>
                  
                  <label class="option-card">
                     <input type="radio" name="delivery_method" value="pickup" required>
                     <div>
                        <strong>Pickup Station</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                           Collect from our nearest station
                        </p>
                     </div>
                  </label>
               </div>
            </div>

            <!-- Conditional Fields for Delivery Method -->
            <div id="deliveryFields" class="conditional-field">
               <div class="form-group">
                  <label for="address">Delivery Address *</label>
                  <textarea id="address" name="address" class="form-control" rows="3" 
                            placeholder="Enter your complete delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
               </div>
            </div>

            <div id="pickupFields" class="conditional-field">
               <div class="form-group">
                  <label>Select Pickup Station *</label>
                  <div class="pickup-stations">
                     <label class="station-card">
                        <input type="radio" name="pickup_station" value="Kihumuro campus (Joel's place)">
                         <strong>Kihumuro campus (Aubrey's Teen)</strong>
                         <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #666;">
                            Open: 8:00 AM - 8:00 PM
                         </p>
                     </label>
                     
                     <label class="station-card">
                        <input type="radio" name="pickup_station" value="Town campus (Campus-canteen)">
                         <strong>Town campus (Campus-canteen)</strong>
                         <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #666;">
                            Open: 8:00 AM - 6:00 PM
                         </p>
                     </label>
                     
                     <label class="station-card">
                        <input type="radio" name="pickup_station" value="Mile 3 (MM building shop-4)">
                         <strong>Mile 3 (MM building shop-4)</strong>
                         <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #666;">
                            Open: 9:00 AM - 7:00 PM
                         </p>
                     </label>
                     
                     <label class="station-card">
                        <input type="radio" name="pickup_station" value="Mile 4 (Maama Berinda's place)">
                         <strong>Mile 4 (Maama Berinda's place)</strong>
                         <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #666;">
                            Open: 8:00 AM - 8:00 PM
                         </p>
                     </label>
                     
                     <label class="station-card">
                        <input type="radio" name="pickup_station" value="Kateete (Pioneer building-shop 2)">
                         <strong>Kateete (Pioneer building-shop 2)</strong>
                         <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #666;">
                            Open: 8:00 AM - 6:00 PM
                         </p>
                     </label>
                  </div>
               </div>
            </div>

            <!-- Payment Method -->
            <div class="form-group">
               <label>Payment Method *</label>
               <div class="payment-options">
                  <label class="option-card">
                     <input type="radio" name="payment_method" value="mobile_money" required>
                     <div>
                        <strong>Mobile Money</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                           Pay with MTN or Airtel Money
                        </p>
                     </div>
                  </label>
                  
                  <label class="option-card">
                     <input type="radio" name="payment_method" value="cash_on_delivery" required>
                     <div>
                        <strong>Cash on Delivery</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                           Pay when you receive items
                        </p>
                     </div>
                  </label>
               </div>
            </div>

            <!-- Conditional Fields for Mobile Money -->
            <div id="mobileMoneyFields" class="conditional-field">
               <div class="form-group">
                  <label for="mobile_money_provider">Mobile Money Provider *</label>
                  <select id="mobile_money_provider" name="mobile_money_provider" class="form-control">
                     <option value="">Select Provider</option>
                     <option value="mtn momo">MTN Mobile Money</option>
                     <option value="airtel money">Airtel Money</option>
                  </select>
               </div>
               
               <div class="form-group">
                  <label for="mobile_money_number">Mobile Money Number *</label>
                  <input type="tel" id="mobile_money_number" name="mobile_money_number" 
                         class="form-control" placeholder="Enter your mobile money number">
               </div>
            </div>

            <button type="submit" name="place_order" class="place-order-btn" id="placeOrderBtn">
               <i class="fas fa-shopping-bag"></i> Confirm & Place Order
            </button>
         </form>
      </div>

      <!-- Order Summary -->
      <div class="order-summary-section">
         <h2 class="section-title">Order Summary</h2>
         
         <?php
         $select_cart_items = $conn->prepare("SELECT c.*, p.name, p.image_url, p.price as product_price 
                                            FROM `cart` c 
                                            JOIN `products` p ON c.product_id = p.product_id 
                                            WHERE c.user_id = ?");
         $select_cart_items->execute([$user_id]);
         
         while($cart_item = $select_cart_items->fetch(PDO::FETCH_ASSOC)){
            $item_total = $cart_item['product_price'] * $cart_item['quantity'];
         ?>
         <div class="order-summary-item">
            <img src="uploaded_img/<?= $cart_item['image_url'] ?>" alt="<?= $cart_item['name'] ?>" class="item-image">
            <div class="item-details">
               <div class="item-name"><?= $cart_item['name'] ?></div>
               <div class="item-price">UGX <?= number_format($cart_item['product_price']) ?> x <?= $cart_item['quantity'] ?></div>
            </div>
            <div class="item-total">UGX <?= number_format($item_total) ?></div>
         </div>
         <?php } ?>
         
         <div class="grand-total">
            Grand Total: UGX <?= number_format($grand_total) ?>
         </div>
         
         <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <h4 style="margin: 0 0 0.5rem 0; color: #333;">Delivery Timeline</h4>
            <ul style="margin: 0; padding-left: 1.2rem; color: #666; font-size: 0.9rem;">
               <li><strong>Order Date:</strong> <?= date('F j, Y') ?></li>
               <li><strong>Expected Delivery:</strong> <?= date('F j, Y', strtotime('+1 day')) ?></li>
               <li><strong>Contact Number:</strong> <?= htmlspecialchars($user['contact'] ?? 'Your registered number') ?></li>
            </ul>
         </div>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
   // Delivery method toggle
   const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
   const deliveryFields = document.getElementById('deliveryFields');
   const pickupFields = document.getElementById('pickupFields');
   
   deliveryRadios.forEach(radio => {
      radio.addEventListener('change', function() {
         if(this.value === 'delivery') {
            deliveryFields.classList.add('active');
            pickupFields.classList.remove('active');
            // Make delivery address required
            document.getElementById('address').required = true;
            // Make pickup station not required
            document.querySelectorAll('input[name="pickup_station"]').forEach(station => {
               station.required = false;
            });
         } else {
            deliveryFields.classList.remove('active');
            pickupFields.classList.add('active');
            // Make delivery address not required
            document.getElementById('address').required = false;
            // Make pickup station required
            document.querySelectorAll('input[name="pickup_station"]').forEach(station => {
               station.required = true;
            });
         }
      });
   });
   
   // Payment method toggle
   const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
   const mobileMoneyFields = document.getElementById('mobileMoneyFields');
   
   paymentRadios.forEach(radio => {
      radio.addEventListener('change', function() {
         if(this.value === 'mobile_money') {
            mobileMoneyFields.classList.add('active');
            // Make mobile money fields required
            document.getElementById('mobile_money_provider').required = true;
            document.getElementById('mobile_money_number').required = true;
         } else {
            mobileMoneyFields.classList.remove('active');
            // Make mobile money fields not required
            document.getElementById('mobile_money_provider').required = false;
            document.getElementById('mobile_money_number').required = false;
         }
      });
   });
   
   // Option card selection styling
   document.querySelectorAll('.option-card, .station-card').forEach(card => {
      card.addEventListener('click', function() {
         const radio = this.querySelector('input[type="radio"]');
         if(radio) {
            radio.checked = true;
            // Update styles
            document.querySelectorAll('.option-card, .station-card').forEach(c => {
               c.classList.remove('selected');
            });
            this.classList.add('selected');
            
            // Trigger change event for conditional fields
            if(radio.name === 'delivery_method' || radio.name === 'payment_method') {
               radio.dispatchEvent(new Event('change'));
            }
         }
      });
   });
   
   // Place Order Button - Loading State
   const placeOrderBtn = document.getElementById('placeOrderBtn');
   const orderForm = placeOrderBtn.closest('form');
   
   orderForm.addEventListener('submit', function(e) {
      // Check if form is valid
      if(orderForm.checkValidity()) {
         // Show loading state
         const originalHTML = placeOrderBtn.innerHTML;
         placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Order...';
         placeOrderBtn.disabled = true;
         placeOrderBtn.style.opacity = '0.7';
         placeOrderBtn.style.cursor = 'not-allowed';
      }
   });
});
</script>

<style>
/* Responsive button styling */
@media (max-width: 768px) {
   .order-grid {
      grid-template-columns: 1fr;
   }
   
   .delivery-options,
   .payment-options {
      grid-template-columns: 1fr;
   }
   
   .place-order-btn {
      font-size: 1.6rem;
      padding: 1.8rem;
   }
}

@media (max-width: 480px) {
   .place-order-btn {
      font-size: 1.5rem;
      padding: 1.5rem;
   }
   
   .option-card {
      padding: 1.2rem;
   }
   
   .order-summary-item {
      flex-direction: column;
      align-items: flex-start;
   }
   
   .item-image {
      width: 100%;
      height: 150px;
      margin-bottom: 1rem;
   }
}
</style>

</body>
</html>