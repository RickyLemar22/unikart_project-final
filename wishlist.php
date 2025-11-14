<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
   exit();
}

// Handle remove from wishlist
if(isset($_POST['delete'])){
   $wishlist_id = $_POST['wishlist_id'];
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE wishlist_id = ?");
   $delete_wishlist_item->execute([$wishlist_id]);
   $message[] = 'Item removed from favorites!';
}

if(isset($_POST['add_to_cart'])){
   $product_id = $_POST['pid'];
   $product_price = $_POST['price'];
   $product_qty = $_POST['qty'];
   
   $product_qty = filter_var($product_qty, FILTER_SANITIZE_NUMBER_INT);
   if($product_qty < 1) $product_qty = 1;
   if($product_qty > 99) $product_qty = 99;
   
   $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND product_id = ?");
   $check_cart->execute([$user_id, $product_id]);
   
   if($check_cart->rowCount() > 0){
      $message[] = 'Product already in cart!';
   } else {
      $insert_cart = $conn->prepare("INSERT INTO `cart` (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
      $insert_cart->execute([$user_id, $product_id, $product_qty, $product_price]);
      $message[] = 'Product added to cart!';
      
      // Remove from wishlist after adding to cart
      $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ? AND product_id = ?");
      $delete_wishlist->execute([$user_id, $product_id]);
   }
}

if(isset($_GET['delete_all'])){
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
   $delete_wishlist_item->execute([$user_id]);
   header('location:wishlist.php');
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Favorites - UniKart</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Display Messages -->
<?php if(isset($message)): ?>
    <?php foreach($message as $msg): ?>
        <div class="message" style="background: var(--success-color); color: white; padding: 15px; margin: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
            <span><?= $msg ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();" style="cursor: pointer;"></i>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<section class="modern-wishlist">
   <div class="wishlist-header">
   </div>

   <div class="modern-products-grid">
   <?php
      // Join with products table to get product details
      $select_wishlist = $conn->prepare("SELECT w.*, p.name, p.price, p.image_url, p.product_id 
                                       FROM `wishlist` w 
                                       JOIN `products` p ON w.product_id = p.product_id 
                                       WHERE w.user_id = ?");
      $select_wishlist->execute([$user_id]);
      
      if($select_wishlist->rowCount() > 0){
         while($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="modern-product-card">
      <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['wishlist_id'] ?>">
      <input type="hidden" name="pid" value="<?= $fetch_wishlist['product_id'] ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_wishlist['name']) ?>">
      <input type="hidden" name="price" value="<?= $fetch_wishlist['price'] ?>">
      <input type="hidden" name="image" value="<?= $fetch_wishlist['image_url'] ?>">
      
      <div class="modern-product-image">
         <?php
         $image_file = $fetch_wishlist['image_url'] ?? '';
         $image_path = "uploaded_img/" . $image_file;
         if(!empty($image_file) && file_exists($image_path)): 
         ?>
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($fetch_wishlist['name']) ?>">
         <?php else: ?>
            <div style="background: #f5f5f5; height: 200px; display: flex; align-items: center; justify-content: center; color: #999;">
               <i class="fas fa-image" style="font-size: 3rem;"></i>
            </div>
         <?php endif; ?>
         
         <button type="submit" name="delete" class="modern-wishlist-btn" onclick="return confirm('Remove from favorites?');" style="background: #ff4757;">
            <i class="fas fa-times"></i>
         </button>
      </div>
      
      <div class="modern-product-info">
         <div class="modern-product-name"><?= htmlspecialchars($fetch_wishlist['name']) ?></div>
         <div class="modern-product-price">UGX <?= number_format($fetch_wishlist['price']) ?></div>
         
         <div class="modern-product-controls">
            <input type="number" name="qty" class="modern-qty" min="1" max="99" value="1">
            <button type="submit" name="add_to_cart" class="modern-add-cart">
               <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
         </div>
      </div>
   </form>
   <?php
         }
      } else {
   ?>
      <div class="modern-empty-state">
         <i class="fas fa-heart"></i>
         <h3>No favorites yet</h3>
         <p>You haven't added any products to your favorites. Start exploring and click the heart icon on products you like!</p>
         <a href="home.php" class="modern-add-cart" style="display: inline-block; width: auto; padding: 1rem 2rem;">
            <i class="fas fa-store"></i> Browse Products
         </a>
      </div>
   <?php
      }
   ?>
   </div>

   <?php
   // Calculate grand total
   $grand_total = 0;
   $select_total = $conn->prepare("SELECT SUM(p.price * w.quantity) as total FROM `wishlist` w JOIN `products` p ON w.product_id = p.product_id WHERE w.user_id = ?");
   $select_total->execute([$user_id]);
   $fetch_total = $select_total->fetch(PDO::FETCH_ASSOC);
   $grand_total = $fetch_total['total'] ?? 0;
   ?>

   <?php if($grand_total > 0): ?>
   <div class="modern-cart-summary">
      <div class="cart-summary-header">
         <h3>Wishlist Summary</h3>
         <div class="cart-grand-total">
            Total Value: <span>UGX <?= number_format($grand_total) ?></span>
         </div>
      </div>
      
      <div class="cart-actions">
         <a href="home.php" class="cart-continue-shopping">
            <i class="fas fa-arrow-left"></i> Continue Shopping
         </a>
         <a href="wishlist.php?delete_all" class="cart-delete-all" onclick="return confirm('Delete all items from favorites?');">
            <i class="fas fa-trash"></i> Clear All Favorites
         </a>
      </div>
   </div>
   <?php endif; ?>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    document.querySelectorAll('button[name="add_to_cart"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.disabled = false;
            }, 3000);
        });
    });
    
    // Quantity input validation
    document.querySelectorAll('.modern-qty').forEach(input => {
        input.addEventListener('change', function() {
            const value = parseInt(this.value);
            if(value < 1) this.value = 1;
            if(value > 99) this.value = 99;
            if(isNaN(value)) this.value = 1;
        });
    });
});
</script>

</body>
</html>