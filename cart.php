<?php 
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

if(isset($_POST['delete'])){
   $cart_id = $_POST['cart_id'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE cart_id = ?");
   $delete_cart_item->execute([$cart_id]);
   $message[] = 'Item removed from cart';
}

if(isset($_GET['delete_all'])){
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
   $delete_cart_item->execute([$user_id]);
   header('location:cart.php');
}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE cart_id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'Cart quantity updated';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart - UniKart</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
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

<section class="modern-cart">
   <div class="cart-header">
      <h1>Your Shopping Cart</h1>
      <p>Review and manage your items</p>
   </div>

   <div class="modern-cart-items-grid">
   <?php
      $grand_total = 0;
      // Updated query with JOIN to get product details
      $select_cart = $conn->prepare("SELECT c.*, p.name, p.image_url, p.price as product_price 
                                   FROM `cart` c 
                                   JOIN `products` p ON c.product_id = p.product_id 
                                   WHERE c.user_id = ?");
      $select_cart->execute([$user_id]);
      
      if($select_cart->rowCount() > 0){
         while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
            // Use product_price from products table
            $sub_total = $fetch_cart['product_price'] * $fetch_cart['quantity'];
            $grand_total += $sub_total;
   ?>
   <form action="" method="post" class="modern-cart-item">
      <input type="hidden" name="cart_id" value="<?= $fetch_cart['cart_id']; ?>">
      
      <div class="cart-item-image">
         <img src="uploaded_img/<?= $fetch_cart['image_url']; ?>" alt="<?= $fetch_cart['name']; ?>">
      </div>
      
      <div class="cart-item-details">
         <div class="cart-item-name"><?= $fetch_cart['name']; ?></div>
         <div class="cart-item-price">UGX <?= number_format($fetch_cart['product_price']); ?></div>
         
         <div class="cart-item-controls">
            <input type="number" name="qty" class="cart-qty-input" min="1" max="99" 
                   value="<?= $fetch_cart['quantity']; ?>">
            <button type="submit" class="cart-update-btn" name="update_qty" title="Update Quantity">
               <i class="fas fa-sync-alt"></i>
            </button>
         </div>
         
         <div class="cart-item-subtotal">
            Subtotal: <span>UGX <?= number_format($sub_total); ?></span>
         </div>
      </div>
      
      <div class="cart-item-actions">
         <a href="quick_view.php?pid=<?= $fetch_cart['product_id']; ?>" class="cart-view-btn" title="Quick View">
            <i class="fas fa-eye"></i>
         </a>
         <button type="submit" value="Delete Item" class="cart-delete-btn" name="delete">
             <i class="fas fa-trash"></i> Remove
          </button>
      </div>
   </form>
   <?php
      }
   } else {
   ?>
   <div class="modern-empty-cart" style="grid-column: 1 / -1;">
      <i class="fas fa-shopping-cart"></i>
      <h3>Your cart is empty</h3>
      <p>Browse our shop to add some items to your cart!</p>
      <a href="shop.php" class="btn">
         <i class="fas fa-store"></i> Start Shopping
      </a>
   </div>
   <?php
   }
   ?>
   </div>

   <?php if($select_cart->rowCount() > 0): ?>
   <div class="modern-cart-summary">
      <div class="cart-summary-header">
         <h3>Order Summary</h3>
         <div class="cart-grand-total">
            Grand Total: <span>UGX <?= number_format($grand_total); ?></span>
         </div>
      </div>
      
      <div class="cart-actions">
         <a href="shop.php" class="cart-continue-shopping">
            <i class="fas fa-arrow-left"></i> Continue Shopping
         </a>
         <a href="cart.php?delete_all" class="cart-delete-all delete-all-cart <?= ($grand_total > 0)?'':'disabled'; ?>">
             <i class="fas fa-trash"></i> Clear Cart
          </a>
         <a href="place_order.php" class="cart-checkout <?= ($grand_total > 0)?'':'disabled'; ?>">
            <i class="fas fa-shopping-bag"></i> Place Order
         </a>
      </div>
   </div>
   <?php endif; ?>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>