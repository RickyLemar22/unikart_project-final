<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Get user's wishlist items to show which products are already favorited
$user_wishlist = [];
if($user_id != ''){
    $get_wishlist = $conn->prepare("SELECT product_id FROM `wishlist` WHERE user_id = ?");
    $get_wishlist->execute([$user_id]);
    $user_wishlist = $get_wishlist->fetchAll(PDO::FETCH_COLUMN);
}

// Get category filter if set
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Get search term if set
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get sorting option
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Categories array
$categories = [
    'computing' => 'Computing & Accessories',
    'smartphones' => 'Smartphones, Tablets & Accessories',
    'appliances' => 'Hostel Electrical Appliances', 
    'bedding' => 'Bedding & Interiors',
    'scholastic' => 'Scholastic Materials',
    'groceries' => 'Drinks, Foods & Groceries',
    'personal' => 'Personal Care & Beauty',
    'clothing' => 'Clothing & Fashion',
    'kitchenware' => 'Kitchenware',
    'sports' => 'Sports & Fitness'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>shop</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php 
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != '') {
    include 'components/user_header.php';
} else {
    include 'components/guest_user_header.php';
}
?>

<!-- Display Messages -->
<?php if(isset($message)): ?>
    <?php foreach($message as $msg): ?>
        <div class="message success">
            <span><?= $msg ?></span>
            <i class="fas fa-times"></i>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="modern-layout">
    <aside class="modern-sidebar">
        <h3>Categories</h3>
        <ul class="modern-category-list">
            <li class="modern-category-item <?= $category_filter === '' ? 'active' : '' ?>">
                <a href="shop.php">
                    <i class="fas fa-home"></i>
                    <span>All Products</span>
                </a>
            </li>
            
            <?php foreach($categories as $value => $label): ?>
            <li class="modern-category-item <?= $category_filter === $value ? 'active' : '' ?>">
                <a href="shop.php?category=<?= $value ?>">
                    <i class="fas fa-chevron-right"></i>
                    <span><?= $label ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="modern-content">
        <div class="welcome-banner">
            <h1>Shop All Products</h1>
            <p>Browse our complete collection of campus essentials</p>
        </div>

        <div class="modern-products-grid">

   <?php
   // Build query with filters
   $query = "SELECT * FROM `products` WHERE 1=1";
   $params = [];

   if(!empty($category_filter)){
      $query .= " AND category = ?";
      $params[] = $category_filter;
   }

   if(!empty($search_term)){
      $query .= " AND (name LIKE ? OR description LIKE ?)";
      $params[] = "%$search_term%";
      $params[] = "%$search_term%";
   }

   // Handle sorting - FIXED: Use product_id and created_at from your actual table
   $query .= " ORDER BY ";
   switch($sort){
      case 'price_low':
         $query .= "price ASC";
         break;
      case 'price_high':
         $query .= "price DESC";
         break;
      case 'name':
         $query .= "name ASC";
         break;
      case 'newest':
      default:
         $query .= "created_at DESC, product_id DESC";
         break;
   }

   $select_products = $conn->prepare($query);
   $select_products->execute($params);
   
   if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
         $is_favorited = in_array($fetch_product['product_id'], $user_wishlist);
   ?>
   <form action="" method="post" class="modern-product-card">
      <input type="hidden" name="pid" value="<?= $fetch_product['product_id'] ?>">
      <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name']) ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price'] ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_url'] ?>">
      <input type="hidden" name="qty" value="1">
      
      <div class="modern-product-image">
         <?php
         $image_file = $fetch_product['image_url'] ?? '';
         $image_path = "uploaded_img/" . $image_file;
         if(!empty($image_file) && file_exists($image_path)): 
         ?>
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($fetch_product['name']) ?>">
         <?php else: ?>
            <div class="no-image-placeholder">
               <i class="fas fa-image"></i>
            </div>
         <?php endif; ?>
         
         <?php if($user_id != ''): ?>
         <button type="submit" name="add_to_wishlist" class="modern-wishlist-btn <?= $is_favorited ? 'favorited' : '' ?>" <?= $is_favorited ? 'disabled' : '' ?>>
            <i class="<?= $is_favorited ? 'fas' : 'far' ?> fa-heart"></i>
         </button>
         <?php else: ?>
         <a href="user_login.php" class="modern-wishlist-btn">
            <i class="far fa-heart"></i>
         </a>
         <?php endif; ?>
      </div>
      
      <div class="modern-product-info">
         <div class="modern-product-name"><?= htmlspecialchars($fetch_product['name']) ?></div>
         <div class="modern-product-category"><?= $categories[$fetch_product['category']] ?? $fetch_product['category'] ?></div>
         <div class="modern-product-price">UGX <?= number_format($fetch_product['price']) ?></div>
         
         <?php if($fetch_product['stock'] > 0): ?>
            <div class="stock-available">In Stock</div>
         <?php else: ?>
            <div class="stock-out">Out of Stock</div>
         <?php endif; ?>
         
         <div class="modern-product-controls">
            <?php if($fetch_product['stock'] > 0): ?>
               <?php if($user_id != ''): ?>
                  <button type="submit" name="add_to_cart" class="modern-add-cart">
                     <i class="fas fa-shopping-cart"></i> Add to Cart
                  </button>
               <?php else: ?>
                  <a href="user_login.php" class="modern-add-cart" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                     <i class="fas fa-shopping-cart"></i> Add to Cart
                  </a>
               <?php endif; ?>
            <?php else: ?>
               <button type="button" class="modern-add-cart" disabled style="background: var(--light-color); color: var(--light-color);">
                  <i class="fas fa-times"></i> Out of Stock
               </button>
            <?php endif; ?>
         </div>
      </div>
   </form>
   <?php
      }
   }else{
   ?>
   <div class="modern-empty-state">
      <i class="fas fa-box-open"></i>
      <h3>No products found</h3>
      <p>Try adjusting your search or browse all products!</p>
      <a href="shop.php" class="modern-add-cart browse-btn">
         <i class="fas fa-store"></i> Browse All Products
      </a>
   </div>
   <?php
   }
   ?>

        </div>
    </main>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>