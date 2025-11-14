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

if (!$fetch_profile) {
    die('Admin not found!');
}

if (isset($_POST['submit'])) {

    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $dob = $_POST['dob'] ?? null;

    // Update name, email, and dob
    $update_profile = $conn->prepare("UPDATE `admins` SET name = ?, email = ?, dob = ? WHERE id = ?");
    $update_profile->execute([$name, $email, $dob, $admin_id]);

    // Password update logic
    $prev_pass = $fetch_profile['password'];
    $old_pass = $_POST['old_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    if (!empty($old_pass)) {
        if (!password_verify($old_pass, $prev_pass)) {
            $message[] = 'Old password not matched!';
        } elseif ($new_pass !== $confirm_pass) {
            $message[] = 'Confirm password not matched!';
        } else {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_admin_pass = $conn->prepare("UPDATE `admins` SET password = ? WHERE id = ?");
            $update_admin_pass->execute([$hashed_pass, $admin_id]);
            $message[] = 'Password updated successfully!';
        }
    }

    $message[] = 'Profile updated successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Profile</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="form-container">
    <form action="" method="post">
        <h3>Update Profile</h3>

        <input type="text" name="name" class="box" required
            value="<?= htmlspecialchars($fetch_profile['name'] ?? '', ENT_QUOTES); ?>"
            placeholder="Enter username">

        <input type="email" name="email" class="box"
            value="<?= htmlspecialchars($fetch_profile['email'] ?? '', ENT_QUOTES); ?>"
            placeholder="Enter email">

        <input type="date" name="dob" class="box"
            value="<?= htmlspecialchars($fetch_profile['dob'] ?? '', ENT_QUOTES); ?>">

        <input type="password" name="old_pass" class="box" placeholder="Enter old password">
        <input type="password" name="new_pass" class="box" placeholder="Enter new password">
        <input type="password" name="confirm_pass" class="box" placeholder="Confirm new password">

        <input type="submit" name="submit" class="btn" value="Update Profile">
    </form>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
