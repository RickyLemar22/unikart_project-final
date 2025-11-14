<?php
include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
if(!$admin_id){
    header('location:admin_login.php');
    exit;
}

// Delete user and related info
if(isset($_GET['delete'])){
    $delete_id = intval($_GET['delete']); // ensure integer
    
    // Get university_email to delete from student table
    $get_user = $conn->prepare("SELECT university_email FROM `user_account` WHERE account_id = ?");
    $get_user->execute([$delete_id]);
    $user = $get_user->fetch(PDO::FETCH_ASSOC);
    
    if($user && $user['university_email']) {
        // Delete related records from other tables first (due to foreign key constraints)
        $tables_to_delete = ['orders', 'messages', 'cart', 'wishlist'];
        foreach($tables_to_delete as $table){
            try {
                $stmt = $conn->prepare("DELETE FROM `$table` WHERE account_id = ?");
                $stmt->execute([$delete_id]);
            } catch (PDOException $e) {
                // Table might not exist or have different column name, continue
                continue;
            }
        }
        
        // Delete from user_account
        $delete_user = $conn->prepare("DELETE FROM `user_account` WHERE account_id = ?");
        $delete_user->execute([$delete_id]);
        
        // Then delete from student table
        $delete_student = $conn->prepare("DELETE FROM `student` WHERE university_email = ?");
        $delete_student->execute([$user['university_email']]);
        
        $message[] = 'User account deleted successfully!';
    }

    header('location:users_accounts.php');
    exit;
}

// Fetch all users with student information (JOIN on university_email)
$select_accounts = $conn->prepare("
    SELECT 
        ua.account_id,
        ua.university_email,
        ua.created_at,
        ua.account_status,
        ua.email_verified,
        ua.last_login,
        ua.login_attempts,
        s.student_id,
        s.full_name,
        s.contact,
        s.address,
        s.faculty,
        s.year_of_study
    FROM `user_account` ua 
    LEFT JOIN `student` s ON ua.university_email = s.university_email 
    ORDER BY ua.account_id DESC
");
$select_accounts->execute();
$users = $select_accounts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unikart | User Accounts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
<style>
    .accounts {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .accounts h1 {
        text-align: center;
        margin-bottom: 3rem;
        font-size: 3rem;
        color: #333;
    }
    
    .box-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
    }
    
    .box {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .box:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transform: translateY(-5px);
    }
    
    .box p {
        margin: 1rem 0;
        font-size: 1.4rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        line-height: 1.6;
    }
    
    .box p strong {
        color: #555;
        font-weight: 600;
        flex-shrink: 0;
        margin-right: 1rem;
    }
    
    .box p span {
        color: #333;
        text-align: right;
        word-break: break-word;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 1.2rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-suspended {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .action-buttons {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 0.8rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 1.3rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-warning {
        background: #f39c12;
        color: white;
    }
    
    .btn-warning:hover {
        background: #e67e22;
        transform: translateY(-2px);
    }
    
    .btn-success {
        background: #27ae60;
        color: white;
    }
    
    .btn-success:hover {
        background: #229954;
        transform: translateY(-2px);
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }
    
    .empty-message {
        text-align: center;
        font-size: 2rem;
        color: #999;
        padding: 4rem;
        grid-column: 1 / -1;
    }
    
    .empty-message i {
        font-size: 5rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .box-container {
            grid-template-columns: 1fr;
        }
        
        .box p {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.3rem;
        }
        
        .box p span {
            text-align: left;
        }
    }
</style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="accounts">
    <h1 class="heading">User Accounts Management</h1>

    <?php if(isset($message)): ?>
        <?php foreach($message as $msg): ?>
            <div class="message">
                <span><?= htmlspecialchars($msg); ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="box-container">
        <?php if(count($users) > 0): ?>
            <?php foreach($users as $user): ?>
                <div class="box">
                    <p><strong>Account ID:</strong> <span>#<?= htmlspecialchars($user['account_id']); ?></span></p>
                    <p><strong>Student ID:</strong> <span><?= htmlspecialchars($user['student_id'] ?? 'N/A'); ?></span></p>
                    
                    <p><strong>Full Name:</strong> <span><?= htmlspecialchars($user['full_name'] ?? 'Not Provided'); ?></span></p>
                    
                    <p><strong>Email:</strong> <span><?= htmlspecialchars($user['university_email']); ?></span></p>
                    <p><strong>Contact:</strong> <span><?= htmlspecialchars($user['contact'] ?? 'Not Provided'); ?></span></p>
                    <p><strong>Faculty:</strong> <span><?= htmlspecialchars($user['faculty'] ?? 'Not Provided'); ?></span></p>
                    <p><strong>Year of Study:</strong> <span><?= $user['year_of_study'] ? 'Year ' . htmlspecialchars($user['year_of_study']) : 'Not Provided'; ?></span></p>
                    
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?= htmlspecialchars($user['account_status'] ?? 'active'); ?>">
                            <?= htmlspecialchars(ucfirst($user['account_status'] ?? 'active')); ?>
                        </span>
                    </p>
                    
                    <p><strong>Email Verified:</strong> 
                        <span class="verified-badge" style="color: <?= $user['email_verified'] ? '#27ae60' : '#e74c3c'; ?>">
                            <i class="fas fa-<?= $user['email_verified'] ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?= $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                        </span>
                    </p>
                    
                    <p><strong>Registered:</strong> 
                        <span><?= date('M j, Y', strtotime($user['created_at'])); ?></span>
                    </p>
                    
                    <?php if($user['last_login']): ?>
                        <p><strong>Last Login:</strong> 
                            <span><?= date('M j, Y g:i A', strtotime($user['last_login'])); ?></span>
                        </p>
                    <?php endif; ?>
                    
                    <?php if($user['login_attempts'] > 0): ?>
                        <p><strong>Failed Logins:</strong> 
                            <span style="color: <?= $user['login_attempts'] >= 3 ? '#e74c3c' : '#f39c12'; ?>">
                                <?= htmlspecialchars($user['login_attempts']); ?> attempt(s)
                            </span>
                        </p>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <?php if($user['account_status'] == 'suspended'): ?>
                            <a href="update_user_status.php?account_id=<?= $user['account_id']; ?>&status=active" 
                               class="action-btn btn-success">
                               <i class="fas fa-check"></i> Activate
                            </a>
                        <?php else: ?>
                            <a href="update_user_status.php?account_id=<?= $user['account_id']; ?>&status=suspended" 
                               class="action-btn btn-warning"
                               onclick="return confirm('Are you sure you want to suspend this account?');">
                               <i class="fas fa-ban"></i> Suspend
                            </a>
                        <?php endif; ?>
                        
                        <a href="users_accounts.php?delete=<?= $user['account_id']; ?>" 
                           onclick="return confirm('⚠️ DELETE THIS ACCOUNT?\n\nThis will permanently delete:\n• User account\n• Student information\n• All orders\n• All messages\n• Cart items\n• Wishlist items\n\nThis action CANNOT be undone!');" 
                           class="action-btn btn-danger">
                           <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-message">
                <i class="fas fa-users-slash"></i>
                <p>No user accounts found!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>