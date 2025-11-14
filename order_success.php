<?php
session_start();

// Check if order was successful
if(!isset($_SESSION['order_success']) || !isset($_SESSION['order_id'])) {
    header('location: home.php');
    exit;
}

$order_id = $_SESSION['order_id'];

// Clear session variables
unset($_SESSION['order_success']);
unset($_SESSION['order_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - UniKart</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Order Success Notification -->
<div class="order-success-notification" id="orderNotification">
    <div class="notification-header">
        <div class="notification-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="notification-title">
            <h3>Order Placed Successfully!</h3>
            <p>Thank you for your purchase</p>
        </div>
    </div>
    
    <div class="notification-body">
        <p>Your order has been received and is being processed.</p>
        <span class="notification-order-id">Order #<?= $order_id ?></span>
    </div>
    
    <div class="notification-actions">
        <a href="home.php" class="notification-close">Continue Shopping</a>
        <a href="orders.php" class="notification-view-order">
            <i class="fas fa-eye"></i> View Order
        </a>
    </div>
</div>

<script src="js/script.js"></script>

</body>
</html>
