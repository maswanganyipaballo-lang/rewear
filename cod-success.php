<?php
session_start();
include 'db.php';

$order_id = isset($_SESSION['cod_order_id']) ? intval($_SESSION['cod_order_id']) : null;
if ($order_id) { unset($_SESSION['cod_order_id']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed — ReWear</title>
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
        <div class="success-icon">🤝</div>
        <h3 style="font-family:'Playfair Display',serif;">Order Placed!</h3>
        <p style="color:var(--mid);margin:0.75rem 0 0.25rem;">
            <?php echo $order_id ? 'Your order <strong>#'.$order_id.'</strong> has been placed.' : 'Your order has been placed.'; ?>
        </p>
        <p style="font-size:0.85rem;color:var(--light-mid);margin-bottom:0.25rem;">Payment method: <strong>Cash on Delivery</strong></p>
        <p style="font-size:0.82rem;color:var(--light-mid);margin-bottom:1.5rem;">Please have the exact amount ready when your order arrives.</p>
        <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="orders.php" style="display:inline-block;background:var(--forest);color:white;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;">View My Orders</a>
            <a href="index.php" style="display:inline-block;background:white;color:var(--forest);border:1.5px solid var(--forest);padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;">Continue Shopping →</a>
        </div>
    </div>
</div>
</body>
</html>
