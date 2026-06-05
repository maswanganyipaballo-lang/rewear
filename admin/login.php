<?php
session_start();
include '../db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email='$email' AND role='admin'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['user_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No admin account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="auth-page" style="padding-top:0;">

<div class="container py-5">
    <div class="auth-card">
        <div class="auth-logo" style="font-size:1.4rem;">🔐 Admin</div>
        <p class="auth-sub">ReWear administration panel</p>

        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="form-label-custom">Admin Email</label>
            <input type="email" name="email" class="form-control-custom" placeholder="admin@rewear.co.za" required>

            <label class="form-label-custom">Password</label>
            <input type="password" name="password" class="form-control-custom" placeholder="Password" required>

            <button type="submit" name="login" class="btn-primary-custom">Sign In →</button>
        </form>

        <div style="text-align:center;margin-top:0.75rem;">
            <a href="../index.php" style="font-size:0.8rem;color:var(--light-mid);text-decoration:none;">← Back to shop</a>
        </div>
    </div>
</div>

</body>
</html>
