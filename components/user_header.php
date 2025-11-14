<?php
// Include session timeout check
include __DIR__ . '/session_timeout.php';

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

// Get user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Get counts for header
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
            <a href="external_products_display.php" class="uniform-icon external-icon" title="Global Products">
                <i class="fas fa-globe"></i>
                <span class="icon-text">Global</span>
            </a>
            
            <a href="contact.php" class="uniform-icon help-icon" title="Help">
                <i class="fas fa-phone"></i>
                <span class="icon-text">Help</span>
            </a>
            
            <?php if($user_id): ?>
                <!-- User is logged in - Show dropdown menu -->
                <div class="user-dropdown">
                    <button class="uniform-icon account-icon user-dropdown-btn" title="My Account">
                        <i class="fas fa-user"></i>
                        <span class="icon-text">My Account</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    
                    <div class="user-dropdown-menu">
                        
                        <div class="dropdown-links">
                            <a href="update_user.php" class="dropdown-link">
                                <i class="fas fa-user-edit"></i>
                                <span>Update Profile</span>
                            </a>
                            
                            <a href="orders.php" class="dropdown-link">
                                <i class="fas fa-shopping-bag"></i>
                                <span>My Orders</span>
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <!-- Logout link that opens modal -->
                            <a href="#" onclick="openLogoutModal(); return false;" class="dropdown-link logout-link">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- User is not logged in - Show login button -->
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

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <i class="fas fa-sign-out-alt"></i>
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="logout-modal-footer">
            <button class="logout-cancel-btn" onclick="closeLogoutModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <a href="user_logout.php" class="logout-confirm-btn">
                <i class="fas fa-sign-out-alt"></i> Yes, Logout
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add scroll effect for header
    const header = document.querySelector('.uniform-header');
    
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.style.background = 'linear-gradient(135deg, #5568d3 0%, #6a489c 100%)';
                header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.15)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            }
        });
    }

    // Dropdown functionality
    const dropdownBtns = document.querySelectorAll('.user-dropdown-btn');
    
    dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.user-dropdown');
            
            // Toggle current dropdown
            dropdown.classList.toggle('active');
            
            // Close all other dropdowns
            document.querySelectorAll('.user-dropdown').forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('active');
                }
            });
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            closeLogoutModal();
        }
    });
});

// Logout Modal Functions
function openLogoutModal() {
    document.getElementById('logoutModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('logoutModal');
    if (e.target === modal) {
        closeLogoutModal();
    }
});
</script>