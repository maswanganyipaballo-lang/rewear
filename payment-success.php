<?php
session_start();
include 'db.php';

$order_id = isset($_SESSION['pending_order_id']) ? intval($_SESSION['pending_order_id']) : null;

// Update order status to confirmed
if ($order_id) {
    mysqli_query($conn, "UPDATE orders SET status='confirmed', payment_method='PayFast' WHERE order_id=$order_id");
    unset($_SESSION['pending_order_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
    </div>
</nav>
<div class="container" style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding-top:80px;">
    <div class="success-card" style="text-align:center;max-width:480px;">
        <div class="success-icon">🎉</div>
        <h3 style="font-family:'Playfair Display',serif;">Payment Successful!</h3>
        <p style="color:var(--mid);margin:0.75rem 0 0.5rem;">Thank you for your purchase.<?php echo $order_id ? ' Your order <strong>#'.$order_id.'</strong> has been confirmed.' : ''; ?></p>
        <p style="font-size:0.82rem;color:var(--light-mid);margin-bottom:1.5rem;">Your order has been confirmed and paid.</p>
        <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="orders.php" style="display:inline-block;background:var(--forest);color:white;padding:0.8rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;">View My Orders</a>
            <a href="index.php" class="btn-checkout" style="display:inline-block;width:auto;padding:0.8rem 1.5rem;">Continue Shopping →</a>
        </div>
    </div>
</div>
</body>
</html>
