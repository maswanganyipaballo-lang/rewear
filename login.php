<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error    = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$msg      = isset($_GET['msg']) ? $_GET['msg'] : '';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'admin') {
                $_SESSION['admin_id']   = $user['user_id'];
                $_SESSION['admin_name'] = $user['full_name'];
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] === 'seller') {
                header("Location: seller-dashboard.php");
            } elseif ($redirect) {
                header("Location: " . basename($redirect));
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page" style="padding-top:0;">

<div class="container py-5">
    <div class="auth-card">
        <div class="auth-logo">Re<span>Wear</span></div>
        <p class="auth-sub">Welcome back — sign in to your account</p>

        <?php if ($msg === 'login_required'): ?>
            <div class="alert-custom alert-info-custom" style="background:#fff8f0;border-color:#f0d5b0;color:#856404;">
                🔒 Please <strong>login or create an account</strong> to purchase items.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="form-label-custom">Email Address</label>
            <input type="email" name="email" class="form-control-custom" placeholder="you@gmail.com" required>

            <label class="form-label-custom">Password</label>
            <div style="position:relative;">
                <input type="password" name="password" id="passwordInput" class="form-control-custom" placeholder="Your password" required style="padding-right:2.8rem;">
                <button type="button" onclick="togglePw()" style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--light-mid);" id="eyeBtn">👁</button>
            </div>

            <button type="submit" name="login" class="btn-primary-custom">Sign In →</button>
        </form>

        <div class="auth-footer-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>

        <div style="text-align:center;margin-top:0.75rem;">
            <a href="index.php" style="font-size:0.8rem;color:var(--light-mid);text-decoration:none;">← Back to shop</a>
        </div>
    </div>
</div>
<script>
function togglePw() {
    const field = document.getElementById('passwordInput');
    const btn   = document.getElementById('eyeBtn');
    if (field.type === 'password') { field.type = 'text'; btn.textContent = '🙈'; }
    else { field.type = 'password'; btn.textContent = '👁'; }
}
</script>
</body>
</html>
