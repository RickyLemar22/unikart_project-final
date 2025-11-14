<?php
include 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

include 'components/wishlist_cart.php';

// Get user's wishlist items to show which products are already favorited
$user_wishlist = [];
if($user_id != ''){
    $get_wishlist = $conn->prepare("SELECT product_id FROM `wishlist` WHERE user_id = ?");
    $get_wishlist->execute([$user_id]);
    $user_wishlist = $get_wishlist->fetchAll(PDO::FETCH_COLUMN);
}

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

// Handle category filter
$current_category_value = '';
if(isset($_GET['category']) && array_key_exists($_GET['category'], $categories)){
    $current_category_value = $_GET['category'];
}

// Product query
if ($current_category_value !== '') {
    $select_products = $conn->prepare("SELECT * FROM `products` WHERE category = ? LIMIT 100");
    $select_products->execute([$current_category_value]);
} else {
    $select_products = $conn->prepare("SELECT * FROM `products` LIMIT 100");
    $select_products->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniKart - Campus Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <div class="message" style="background: var(--success-color); color: white; padding: 15px; margin: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
                <span><?= $msg ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();" style="cursor: pointer;"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="modern-layout">
        <aside class="modern-sidebar">
            <h3>Categories</h3>
            <ul class="modern-category-list">
                <li class="modern-category-item <?= $current_category_value === '' ? 'active' : '' ?>">
                    <a href="home.php">
                        <i class="fas fa-home"></i>
                        <span>All Products</span>
                    </a>
                </li>
                
                <?php foreach($categories as $value => $label): ?>
                <li class="modern-category-item <?= $current_category_value === $value ? 'active' : '' ?>">
                    <a href="home.php?category=<?= $value ?>">
                        <i class="fas fa-chevron-right"></i>
                        <span><?= $label ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="modern-content">
            <div class="welcome-banner">
                <h1>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        Welcome to Unikart
                    <?php else: ?>
                        Welcome to UniKart
                    <?php endif; ?>
                </h1>
                <p>Your one-stop shop for all campus essentials. Fast delivery right to your place of residence</p>
                
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div style="margin-top: 1rem;">
                        <a href="user_register.php" class="modern-add-cart" style="display: inline-block; width: auto; padding: 1rem 2rem; margin-right: 1rem;">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-header">
                <h2 class="heading">
                    <?= $current_category_value !== '' ? $categories[$current_category_value] : 'Featured Products' ?>
                </h2>
            </div>

            <div class="modern-products-grid">
                <?php if($select_products->rowCount() > 0): ?>
                    <?php while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)): 
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="modern-empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>No products available in this category yet.</p>
                        <a href="home.php" class="modern-add-cart browse-btn">
                            <i class="fas fa-store"></i> Browse All Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($select_products->rowCount() > 0): ?>
            <div class="load-more-section">
                <a href="home.php<?= $current_category_value ? '?category=' . $current_category_value : '' ?>" class="modern-add-cart load-more-btn">
                    <i class="fas fa-redo"></i> Load More Products
                </a>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/script.js"></script>
	<!-- In your existing home.php - just use the API functions directly -->
<script>
// This works because unikart-api.js is already loaded in footer
document.addEventListener('DOMContentLoaded', function() {
    // Example: Load products when page loads
    loadProductsFromAPI();
});

// Direct usage - no need to define these in separate scripts
async function loadProductsFromAPI() {
    try {
        const result = await unikartAPI.getProducts({
            category: '<?= $current_category_value ?>',
            limit: 12
        });
        
        if (result.data && result.data.products) {
            console.log('Products loaded via API:', result.data.products);
            // Update your product grid here if needed
        }
    } catch (error) {
        console.log('API not available, using normal PHP loading');
    }
}
</script>
</body>
</html>
