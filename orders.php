<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Orders - UniKart</title>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>

<body>

<?php include 'components/user_header.php'; ?>

<section class="modern-orders">
   <div class="orders-header">
      <h1>My Orders</h1>
      <p>Track and manage your purchases</p>
   </div>

   <div class="orders-grid">

   <?php if($user_id == '') { ?>

      <div class="login-prompt">
         <i class="fas fa-sign-in-alt"></i>
         <h3>Login Required</h3>
         <p>Please login to view your order history</p>
         <a href="user_login.php" class="btn">Login Now</a>
      </div>

   <?php } else {

      // FIX: use account_id and created_at
      $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE account_id = ? ORDER BY created_at DESC");
      $select_orders->execute([$user_id]);

      if($select_orders->rowCount() > 0){
         while($order = $select_orders->fetch(PDO::FETCH_ASSOC)){
   ?>

   <div class="modern-order-card">

      <div class="order-header">
         <div class="order-date">
            <i class="fas fa-calendar"></i>
            <?= date('F j, Y', strtotime($order['created_at'])); ?>
         </div>

         <div class="order-id">
            <i class="fas fa-receipt"></i>
            Order #<?= $order['order_id']; ?>
         </div>
      </div>

      <div class="order-details">
         <div class="detail-group">
            <span class="detail-label">Delivery Method</span>
            <span class="detail-value"><?= ucfirst($order['delivery_method']); ?></span>
         </div>

         <?php if($order['delivery_method'] == 'delivery') { ?>
         <div class="detail-group">
            <span class="detail-label">Delivery Address</span>
            <span class="detail-value"><?= $order['delivery_address']; ?></span>
         </div>
         <?php } else { ?>
         <div class="detail-group">
            <span class="detail-label">Pickup Station</span>
            <span class="detail-value"><?= $order['pickup_station']; ?></span>
         </div>
         <?php } ?>
         
         <div class="detail-group">
            <span class="detail-label">Payment</span>
            <span class="detail-value"><?= ucfirst($order['payment_method']); ?></span>
         </div>

         <div class="detail-group">
            <span class="detail-label">Order Status</span>
            <span class="detail-value"><?= ucfirst($order['order_status']); ?></span>
         </div>
      </div>

      <div class="order-summary">
         <div class="summary-row">
            <span class="summary-label">Total Amount</span>
            <span class="summary-value">UGX <?= number_format($order['total_amount']); ?></span>
         </div>
      </div>

      <div class="order-actions">
         <a href="#" class="action-btn view-order">
            <i class="fas fa-eye"></i> View Details
         </a>
      </div>

   </div>

   <?php
         }
      } else {
   ?>

      <div class="modern-empty-orders">
         <i class="fas fa-shopping-bag"></i>
         <h3>No Orders Yet</h3>
         <p>You haven't placed any orders.</p>
         <a href="shop.php" class="btn">Start Shopping</a>
      </div>

   <?php } } ?>

   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
