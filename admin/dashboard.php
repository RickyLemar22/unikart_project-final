<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:admin_login.php');
    exit;
}

// Fetch admin profile
$select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Fetch order totals using your actual column names
try {
    // Using total_amount from orders table and order_status
    $total_pendings = $conn->query("SELECT SUM(total_amount) AS total FROM `orders` WHERE order_status='pending'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_completes = $conn->query("SELECT SUM(total_amount) AS total FROM `orders` WHERE order_status='delivered'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Count paid orders separately
    $total_paid = $conn->query("SELECT SUM(total_amount) AS total FROM `orders` WHERE order_status='paid'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

} catch (PDOException $e) {
    $total_pendings = 0;
    $total_completes = 0;
    $total_paid = 0;
}

// Count rows using your actual table structures
try {
    $number_of_orders = $conn->query("SELECT COUNT(*) AS count FROM `orders`")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $number_of_orders = 0;
}

try {
    $number_of_products = $conn->query("SELECT COUNT(*) AS count FROM `products`")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $number_of_products = 0;
}

try {
    $number_of_users = $conn->query("SELECT COUNT(*) AS count FROM `user_account`")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $number_of_users = 0;
}

try {
    $number_of_admins = $conn->query("SELECT COUNT(*) AS count FROM `admins`")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $number_of_admins = 0;
}

try {
    $number_of_messages = $conn->query("SELECT COUNT(*) AS count FROM `messages`")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $number_of_messages = 0;
}

// Count orders by status
try {
    $pending_orders_count = $conn->query("SELECT COUNT(*) AS count FROM `orders` WHERE order_status='pending'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    $paid_orders_count = $conn->query("SELECT COUNT(*) AS count FROM `orders` WHERE order_status='paid'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    $delivered_orders_count = $conn->query("SELECT COUNT(*) AS count FROM `orders` WHERE order_status='delivered'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (PDOException $e) {
    $pending_orders_count = 0;
    $paid_orders_count = 0;
    $delivered_orders_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="dashboard">
    <h1 class="heading">Dashboard</h1>

    <div class="box-container">

        <!-- Welcome Card -->
        <div class="box card welcome-card">
            <h3><?= htmlspecialchars($fetch_profile['full_name']); ?></h3>
            <p>Manage the Unikart store efficiently.</p>
        </div>

        <!-- Pending Orders Amount -->
        <div class="box card">
            <h3><i class="fas fa-hourglass-half"></i> UGx<?= number_format($total_pendings, 2); ?></h3>
            <p>Pending Orders Value</p>
            <a href="placed_orders.php?status=pending" class="btn btn-primary">See Pending</a>
        </div>

        <!-- Paid Orders Amount -->
        <div class="box card">
            <h3><i class="fas fa-credit-card"></i> UGx<?= number_format($total_paid, 2); ?></h3>
            <p>Paid Orders Value</p>
            <a href="placed_orders.php?status=paid" class="btn btn-primary">See Paid</a>
        </div>

        <!-- Delivered Orders Amount -->
        <div class="box card">
            <h3><i class="fas fa-check-circle"></i> UGx<?= number_format($total_completes, 2); ?></h3>
            <p>Delivered Orders Value</p>
            <a href="placed_orders.php?status=delivered" class="btn btn-primary">See Delivered</a>
        </div>

        <!-- Total Orders Count -->
        <div class="box card">
            <h3><i class="fas fa-shopping-cart"></i> <?= $number_of_orders; ?></h3>
            <p>Total Orders</p>
            <a href="placed_orders.php" class="btn btn-primary">All Orders</a>
        </div>

        <!-- Orders by Status -->
        <div class="box card">
            <h3><i class="fas fa-chart-pie"></i> Order Stats</h3>
            <p>Pending: <?= $pending_orders_count; ?></p>
            <p>Paid: <?= $paid_orders_count; ?></p>
            <p>Delivered: <?= $delivered_orders_count; ?></p>
            <a href="placed_orders.php" class="btn btn-primary">View Details</a>
        </div>

        <!-- Products Count -->
        <div class="box card">
            <h3><i class="fas fa-tags"></i> <?= $number_of_products; ?></h3>
            <p>Total Products</p>
            <a href="products.php" class="btn btn-primary">Manage Products</a>
        </div>

        <!-- Users Count -->
        <div class="box card">
            <h3><i class="fas fa-users"></i> <?= $number_of_users; ?></h3>
            <p>Registered Users</p>
            <a href="users_accounts.php" class="btn btn-primary">Manage Users</a>
        </div>

        <!-- Admins Count -->
        <div class="box card">
            <h3><i class="fas fa-user-shield"></i> <?= $number_of_admins; ?></h3>
            <p>Admin Users</p>
            <a href="admin_accounts.php" class="btn btn-primary">Manage Admins</a>
        </div>

        <!-- Messages Count -->
        <div class="box card">
            <h3><i class="fas fa-envelope"></i> <?= $number_of_messages; ?></h3>
            <p>Customer Messages</p>
            <a href="messages.php" class="btn btn-primary">View Messages</a>
        </div>

    </div>
</main>

<script src="../js/admin_script.js"></script>
</body>
</html>