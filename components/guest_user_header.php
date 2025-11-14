<?php
if(isset($message)){
    foreach($message as $msg){
        echo '
        <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}

// Get counts for header
$user_id = isset($user_id) ? $user_id : 0;

$count_wishlist_items = $conn->prepare("SELECT COUNT(*) FROM `wishlist` WHERE user_id = ?");
$count_wishlist_items->execute([$user_id]);
$total_wishlist_counts = $count_wishlist_items->fetchColumn();

$count_cart_items = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
$count_cart_items->execute([$user_id]);
$total_cart_counts = $count_cart_items->fetchColumn();
?>

<header class="uniform-header">
    <nav class="uniform-nav">
        <a href="home.php" class="uniform-logo">
            <i class="fas fa-shopping-cart"></i>
            UniKart
        </a>
        
        <div class="uniform-search">
            <form action="search_page.php" method="post">
                <input type="text" name="search_box" placeholder="Search products..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        
        <div class="uniform-icons">
            <a href="contact.php" class="uniform-icon help-icon" title="Help">
                <i class="fas fa-phone"></i>
                <span class="icon-text">Help</span>
            </a>
            
            <?php if($user_id): ?>
                <a href="update_user.php" class="uniform-icon account-icon" title="My Account">
                    <i class="fas fa-user"></i>
                    <span class="icon-text">Account</span>
                </a>
            <?php else: ?>
                <a href="user_login.php" class="uniform-icon account-icon" title="Login">
                    <i class="fas fa-user"></i>
                    <span class="icon-text">Login</span>
                </a>
            <?php endif; ?>
            
            <a href="wishlist.php" class="uniform-icon wishlist-icon" title="Wishlist">
                <i class="fas fa-heart"></i>
                <span class="icon-text">Favourites</span>
                <?php if($total_wishlist_counts > 0): ?>
                    <span class="count-badge"><?= $total_wishlist_counts; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="cart.php" class="uniform-icon cart-icon" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="icon-text">Cart</span>
                <?php if($total_cart_counts > 0): ?>
                    <span class="count-badge"><?= $total_cart_counts; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>
</header>