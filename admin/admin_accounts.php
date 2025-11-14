<?php
include '../components/connect.php';
session_start();

// Check if admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:admin_login.php');
    exit;
}

// Delete admin account
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete']; // Ensure integer to prevent SQL injection
    if ($delete_id !== (int)$admin_id) { // Prevent self-deletion
        $delete_stmt = $conn->prepare("DELETE FROM `admins` WHERE id = ?");
        $delete_stmt->execute([$delete_id]);
    }
    header('location:admin_accounts.php');
    exit;
}

// Fetch all admins
$select_accounts = $conn->prepare("SELECT * FROM `admins` ORDER BY id ASC");
$select_accounts->execute();
$admins = $select_accounts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unikart | Admin Accounts</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <?php include '../components/admin_header.php'; ?>

    <main class="accounts">
        <h1 class="heading">Admin Accounts</h1>

        <div class="box-container">

            <!-- Add new admin -->
            <div class="box new-admin">
                <p>Add New Admin</p>
                <a href="register_admin.php" class="btn btn-primary">Register Admin</a>
            </div>

            <?php if ($admins): ?>
                <?php foreach ($admins as $admin): ?>
                    <div class="box">
                        <p><strong>ID:</strong> <?= htmlspecialchars($admin['id']); ?></p>
                        <p><strong>Username:</strong> <?= htmlspecialchars($admin['username']); ?></p>
                        <p><strong>Name:</strong> <?= htmlspecialchars($admin['full_name']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']); ?></p>
                        <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($admin['role'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-<?= $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                <?= $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                        <?php if ($admin['last_login']): ?>
                            <p><strong>Last Login:</strong> <?= date('M j, Y g:i A', strtotime($admin['last_login'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex-btn">
                            <?php if ($admin['id'] != $admin_id): ?>
                                <a href="admin_accounts.php?delete=<?= $admin['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this admin?');">
                                   <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            <?php else: ?>
                                <a href="update_profile.php" class="btn btn-secondary">
                                   <i class="fa-solid fa-user-pen"></i> Update Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty">No admin accounts available!</p>
            <?php endif; ?>

        </div>
    </main>

    <script src="../js/admin_script.js"></script>
</body>
</html>