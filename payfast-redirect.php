<?php
session_start();
if (!isset($_SESSION['payfast_data'])) {
    header("Location: index.php");
    exit();
}
$pf_data    = $_SESSION['payfast_data'];
$sandbox_url = $_SESSION['payfast_sandbox'];
unset($_SESSION['payfast_data'], $_SESSION['payfast_sandbox']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayFast — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f8f8f6; }
        .redirect-card { background:white; border-radius:16px; padding:2.5rem; text-align:center; box-shadow:0 4px 24px rgba(0,0,0,0.08); max-width:420px; width:100%; }
        .spinner { width:48px;height:48px;border:4px solid #eee;border-top-color:var(--clay);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body>
<div class="redirect-card">
    <div class="spinner"></div>
    <h4 style="font-family:'Playfair Display',serif;margin-bottom:0.5rem;">Redirecting to PayFast</h4>
    <p style="color:var(--mid);font-size:0.88rem;">Please wait while we securely redirect you to PayFast Sandbox to complete your payment...</p>
    <p style="font-size:0.75rem;color:var(--light-mid);">🔒 Your payment is processed securely by PayFast</p>

    <form id="pfForm" method="POST" action="<?php echo htmlspecialchars($sandbox_url); ?>">
        <?php foreach ($pf_data as $key => $val): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>">
        <?php endforeach; ?>
        <noscript><button type="submit" style="margin-top:1rem;">Click here if not redirected automatically</button></noscript>
    </form>
</div>
<script>
    setTimeout(function() {
        document.getElementById('pfForm').submit();
    }, 1500);
</script>
</body>
</html>
