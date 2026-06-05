<?php
/**
 * ReWear Admin Setup Script
 * 
 * Run this ONCE to create your admin account.
 * Then DELETE this file for security!
 * 
 * Access: http://localhost/rewear/admin/setup.php
 */

include '../db.php';

$done  = '';
$error = '';

if (isset($_POST['create_admin'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password  = $_POST['password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if exists
        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            // Update existing to admin
            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hash', role='admin', full_name='$full_name' WHERE email='$email'");
            $done = "Updated existing account to admin: $email";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql  = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name','$email','$hash','admin')";
            if (mysqli_query($conn, $sql)) {
                $done = "Admin account created! You can now delete this file and log in.";
            } else {
                $error = "Failed: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Setup — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="auth-page" style="padding-top:0;">
<div class="container py-5">
    <div class="auth-card">
        <div class="auth-logo">⚙️ Admin Setup</div>
        <p class="auth-sub">Create your ReWear admin account. <strong>Delete this file after use!</strong></p>

        <?php if ($done): ?>
            <div class="alert-custom alert-success-custom"><?php echo $done; ?> <a href="login.php">Login →</a></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$done): ?>
        <form method="POST">
            <label class="form-label-custom">Full Name</label>
            <input type="text" name="full_name" class="form-control-custom" value="Admin" required>

            <label class="form-label-custom">Email</label>
            <input type="email" name="email" class="form-control-custom" placeholder="admin@rewear.co.za" required>

            <label class="form-label-custom">Password</label>
            <input type="password" name="password" class="form-control-custom" placeholder="Min 6 characters" required>

            <button type="submit" name="create_admin" class="btn-primary-custom">Create Admin Account</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
