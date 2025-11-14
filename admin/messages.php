<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    header('location:admin_login.php');
    exit;
}

// Delete a message securely
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $delete_message = $conn->prepare("DELETE FROM `messages` WHERE id = ?");
    $delete_message->execute([$delete_id]);
    header('location:messages.php');
    exit;
}

// Fetch messages
$select_messages = $conn->prepare("SELECT * FROM `messages` ORDER BY id DESC");
$select_messages->execute();
$messages = $select_messages->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart | Messages</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<main class="contacts">
    <h1 class="heading">Customer Messages</h1>

    <div class="box-container">
        <?php if ($messages): ?>
            <?php foreach ($messages as $msg): ?>
            <div class="box card">
                <div class="message-header">
                    <h4><i class="fas fa-user"></i> <?= htmlspecialchars($msg['name']); ?> (ID: <?= htmlspecialchars($msg['user_id']); ?>)</h4>
                    <a href="messages.php?delete=<?= $msg['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this message?');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <div class="message-body">
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($msg['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($msg['number']); ?></p>
                    <p><i class="fas fa-comment-dots"></i> <?= htmlspecialchars($msg['message']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">You have no messages.</p>
        <?php endif; ?>
    </div>
</main>

<script src="../js/admin_script.js"></script>
</body>
</html>
