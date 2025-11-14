<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Initialize search variables
$search_box = '';
$search_results = [];

if(isset($_POST['search_box']) || isset($_POST['search_btn'])){
   $search_box = $_POST['search_box'];
   
   // Use the correct column names from your products table
   $select_products = $conn->prepare("
      SELECT 
        product_id,
        name, 
        price, 
        image_url,
        category,
        description,
        stock,
        hot_deal,
        featured
      FROM `products` 
      WHERE name LIKE ? OR category LIKE ? OR description LIKE ?
   "); 
   $select_products->execute(["%$search_box%", "%$search_box%", "%$search_box%"]);
   $search_results = $select_products->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Products - UniKart</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/guest_user_header.php'; ?>

<!-- Search Results Section -->
<section class="search-results">
   <div class="container">
      <?php if(!empty($search_box)): ?>
         <div class="results-header">
            <h3>Search Results for "<?= htmlspecialchars($search_box) ?>"</h3>
            <p class="results-count"><?= count($search_results) ?> product(s) found</p>
         </div>
      <?php endif; ?>

      <div class="modern-products-grid">
         <?php if(!empty($search_results)): ?>
            <?php foreach($search_results as $fetch_product): 
               // Use the correct column names from your database
               $product_id = $fetch_product['product_id'];
               $product_name = $fetch_product['name'];
               $product_price = $fetch_product['price'];
               $product_image = $fetch_product['image_url'];
               $product_stock = $fetch_product['stock'];
            ?>
            <form action="" method="post" class="modern-product-card">
               <input type="hidden" name="pid" value="<?= $product_id; ?>">
               <input type="hidden" name="name" value="<?= htmlspecialchars($product_name); ?>">
               <input type="hidden" name="price" value="<?= $product_price; ?>">
               <input type="hidden" name="image" value="<?= $product_image; ?>">
               
               <div class="modern-product-image">
                  <?php if(!empty($product_image)): ?>
                     <img src="uploaded_img/<?= $product_image; ?>" alt="<?= htmlspecialchars($product_name); ?>">
                  <?php else: ?>
                     <div class="no-image-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No Image</span>
                     </div>
                  <?php endif; ?>
                  <?php if($fetch_product['hot_deal']): ?>
                     <div class="hot-deal-badge">Hot Deal</div>
                  <?php endif; ?>
                  <?php if($fetch_product['featured']): ?>
                     <div class="featured-badge">Featured</div>
                  <?php endif; ?>
                  <button type="submit" name="add_to_wishlist" class="modern-wishlist-btn">
                     <i class="far fa-heart"></i>
                  </button>
               </div>
               
               <div class="modern-product-info">
                  <div class="modern-product-name"><?= htmlspecialchars($product_name); ?></div>
                  <div class="modern-product-category"><?= htmlspecialchars($fetch_product['category'] ?? 'Uncategorized'); ?></div>
                  <div class="modern-product-price">UGX <?= number_format($product_price, 2); ?></div>
                  
                  <div class="modern-product-stock">
                     <?php if($product_stock > 0): ?>
                        <span class="in-stock">In Stock (<?= $product_stock ?> available)</span>
                     <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                     <?php endif; ?>
                  </div>
                  
                  <div class="modern-product-controls">
                     <input type="number" name="qty" class="modern-qty" min="1" max="<?= $product_stock > 0 ? $product_stock : 0 ?>" value="1" <?= $product_stock == 0 ? 'disabled' : '' ?>>
                     <button type="submit" name="add_to_cart" class="modern-add-cart" <?= $product_stock == 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-shopping-cart"></i> 
                        <?= $product_stock > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                     </button>
                  </div>
               </div>
            </form>
            <?php endforeach; ?>
         <?php elseif(!empty($search_box)): ?>
            <div class="modern-empty-state">
               <i class="fas fa-search"></i>
               <h3>No products found</h3>
               <p>We couldn't find any products matching "<?= htmlspecialchars($search_box) ?>".</p>
               <p>Try checking your spelling or using different keywords.</p>
               <a href="shop.php" class="modern-add-cart browse-all-btn">
                  <i class="fas fa-store"></i> Browse All Products
               </a>
            </div>
         <?php else: ?>
            <div class="modern-empty-state">
               <i class="fas fa-search"></i>
               <h3>Start Searching</h3>
               <p>Enter the product name in the search box above to find what you're looking for.</p>
               <div class="search-suggestions">
                  <h4>Popular Searches:</h4>
                  <div class="suggestion-tags">
                     <a href="shop.php?category=smartphones" class="suggestion-tag">Smartphones</a>
                     <a href="shop.php?category=computing" class="suggestion-tag">Laptops</a>
                     <a href="shop.php?category=clothing" class="suggestion-tag">Clothing</a>
                     <a href="shop.php?category=groceries" class="suggestion-tag">Groceries</a>
                  </div>
               </div>
            </div>
         <?php endif; ?>
      </div>
   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>