<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if(!$admin_id){
    header('location:admin_login.php');
    exit;
}

// Update order status
if(isset($_POST['update_status'])){
    $order_id = $_POST['order_id'];
    $order_status = filter_var($_POST['order_status'], FILTER_SANITIZE_SPECIAL_CHARS);
    // CORRECTED: Using order_id and order_status columns
    $update_status = $conn->prepare("UPDATE `orders` SET order_status = ? WHERE order_id = ?");
    $update_status->execute([$order_status, $order_id]);
    $message[] = 'Order status updated!';
}

// Delete an order
if(isset($_GET['delete'])){
    $delete_id = (int)$_GET['delete'];
    // CORRECTED: Using order_id instead of id
    $conn->prepare("DELETE FROM `orders` WHERE order_id = ?")->execute([$delete_id]);
    header('location:placed_orders.php');
    exit;
}

// Fetch orders with user information
// CORRECTED: Using your actual table structure
$select_orders = $conn->prepare("
    SELECT o.*, ua.university_email 
    FROM `orders` o 
    LEFT JOIN `user_account` ua ON o.account_id = ua.account_id 
    ORDER BY o.order_id DESC
");
$select_orders->execute();
$orders = $select_orders->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart | Placed Orders</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="orders">
    <h1 class="heading">Placed Orders</h1>

    <div class="box-container">
        <?php if($orders): ?>
            <?php foreach($orders as $order): ?>
            <div class="box">
                <p><strong>Order ID:</strong> <span>#<?= htmlspecialchars($order['order_id']); ?></span></p>
                <p><strong>Account ID:</strong> <span><?= htmlspecialchars($order['account_id'] ?? 'N/A'); ?></span></p>
                <p><strong>Customer Email:</strong> <span><?= htmlspecialchars($order['university_email'] ?? 'N/A'); ?></span></p>
                <p><strong>Total Amount:</strong> <span>UGx <?= number_format($order['total_amount'], 2); ?></span></p>
                <p><strong>Order Status:</strong> 
                    <span class="status-<?= htmlspecialchars($order['order_status']); ?>">
                        <?= htmlspecialchars(ucfirst($order['order_status'])); ?>
                    </span>
                </p>
                <p><strong>Order Date:</strong> <span><?= htmlspecialchars($order['created_at']); ?></span></p>

                <form action="" method="post" class="update-order-form">
                    <!-- CORRECTED: Using order_id instead of id -->
                    <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">

                    <label for="order_status_<?= $order['order_id']; ?>">Update Status:</label>
                    <select name="order_status" id="order_status_<?= $order['order_id']; ?>" class="select">
                        <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?= $order['order_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>

                    <div class="flex-btn">
                        <input type="submit" name="update_status" value="Update Status" class="option-btn">
                        <a href="placed_orders.php?delete=<?= $order['order_id']; ?>" class="delete-btn" onclick="return confirm('Delete this order?');">Delete</a>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No orders placed yet!</p>
        <?php endif; ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>