<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled — ReWear</title>
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
        <div style="font-size:3rem;margin-bottom:1rem;">😔</div>
        <h3 style="font-family:'Playfair Display',serif;">Payment Cancelled</h3>
        <p style="color:var(--mid);margin:0.75rem 0 1.5rem;">Your payment was cancelled. Your cart items are still saved.</p>
        <a href="cart.php" class="btn-checkout" style="display:inline-block;width:auto;padding:0.8rem 2rem;margin-right:0.5rem;">Back to Cart</a>
        <a href="index.php" style="color:var(--clay);font-size:0.88rem;">Continue Browsing</a>
    </div>
</div>
</body>
</html>
