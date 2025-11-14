<?php
// Display messages
if(isset($message)){
    foreach($message as $msg){
        echo '
        <div class="notification">
            <i class="fas fa-check-circle"></i>
            <span>'.$msg.'</span>
            <i class="fas fa-times notification-close" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>

<!-- Custom Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="modal-body">
            <i class="fas fa-sign-out-alt"></i>
            <p>Are you sure you want to logout, <strong><?= htmlspecialchars($fetch_profile['username'] ?? 'Admin'); ?></strong>?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <a href="../components/admin_logout.php" class="btn-logout">Yes, Logout</a>
        </div>
    </div>
</div>

<header class="header">
    <section class="flex">
        <!-- Logo -->
        <a href="dashboard.php" class="logo">Unikart</a>

        <!-- Navigation -->
        <nav class="navbar" id="navbar">
            <a href="dashboard.php">Home</a>
            <a href="products.php">Products</a>
            <a href="placed_orders.php">Orders</a>
            <a href="admin_accounts.php">Admins</a>
            <a href="users_accounts.php">Users</a>
            <a href="messages.php">Messages</a>
        </nav>

        <!-- Admin Profile Section -->
        <div class="admin-profile">
            <?php
            if(isset($_SESSION['admin_id'])) {
                $admin_id = $_SESSION['admin_id'];
                $select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
                $select_profile->execute([$admin_id]);
                
                if($select_profile->rowCount() > 0) {
                    $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="profile-dropdown">
                        <button class="profile-btn">
                            <i class="fas fa-user-shield"></i>
                            <?= htmlspecialchars($fetch_profile['username']); ?>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <p><strong><?= htmlspecialchars($fetch_profile['full_name']); ?></strong></p>
                            <p><?= htmlspecialchars($fetch_profile['email']); ?></p>
                            <p>Role: <?= htmlspecialchars(ucfirst($fetch_profile['role'])); ?></p>
                            <hr>
                            <a href="update_profile.php"><i class="fas fa-user-edit"></i> Update Profile</a>
                            <!-- Logout Link that opens modal -->
                            <a href="#" onclick="openLogoutModal(); return false;" class="logout-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>

        <!-- Mobile menu button - Only shows on small screens -->
        <div class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </div>
    </section>
</header>