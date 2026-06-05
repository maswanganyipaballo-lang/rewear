<?php
session_start();
include 'db.php';
// Sellers stay on their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    header("Location: seller-dashboard.php");
    exit();
}


$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Remove item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $key = array_search($remove_id, $_SESSION['cart']);
    if ($key !== false) array_splice($_SESSION['cart'], $key, 1);
    header("Location: cart.php");
    exit();
}

// Clear all
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}

$total = 0;
$items = [];
foreach ($cart as $id) {
    $id     = intval($id);
    $res    = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $product = mysqli_fetch_assoc($res);
    if ($product) { $items[] = $product; $total += $product['product_price']; }
}
$cart_count = count($items);
$is_logged_in = isset($_SESSION['user_id']);

// Is admin viewing shop?
$is_admin_view = (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <?php if ($is_admin_view): ?>
    <style>
        .admin-shop-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #1a1a1a; color: white; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: center; gap: 1rem; z-index: 9999; font-size: 0.85rem; }
        body { padding-bottom: 60px; }
    </style>
    <?php endif; ?>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
        <ul class="navbar-nav ms-auto gap-1">
            <li class="nav-item"><a class="nav-link" href="index.php">← Continue Shopping</a></li>
            <?php if ($is_logged_in && !$is_admin_view): ?>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <?php elseif (!$is_logged_in): ?>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container"><h1>Your Cart</h1></div>
</div>

<div class="container mb-5">
<?php if (empty($items)): ?>
    <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <h3>Your cart is empty</h3>
        <p>Browse our collection and add items you love.</p>
        <a href="index.php" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.75rem 2rem;">Browse Items</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-md-8">
            <?php foreach ($items as $product): ?>
            <div style="display:flex;align-items:center;gap:1.2rem;background:white;border-radius:12px;padding:1rem;margin-bottom:1rem;box-shadow:var(--shadow);">
                <img src="<?php echo htmlspecialchars($product['product_image'] ?: 'https://via.placeholder.com/80'); ?>"
                     style="width:80px;height:80px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
                <div style="flex:1;">
                    <div style="font-weight:600;"><?php echo htmlspecialchars($product['product_name']); ?></div>
                    <div style="font-size:0.8rem;color:var(--light-mid);"><?php echo htmlspecialchars($product['category']); ?> · Size <?php echo htmlspecialchars($product['size'] ?: 'N/A'); ?></div>
                </div>
                <div style="font-family:'Playfair Display',serif;font-weight:700;color:var(--forest);">R<?php echo number_format($product['product_price'], 2); ?></div>
                <a href="cart.php?remove=<?php echo $product['product_id']; ?>" style="color:#c0392b;font-size:0.8rem;text-decoration:none;">✕ Remove</a>
            </div>
            <?php endforeach; ?>
            <a href="cart.php?clear=1" style="font-size:0.8rem;color:var(--light-mid);" onclick="return confirm('Clear cart?')">Clear cart</a>
        </div>
        <div class="col-md-4">
            <div class="cart-summary">
                <h4>Order Summary</h4>
                <div class="summary-row"><span><?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?></span><span>R<?php echo number_format($total, 2); ?></span></div>
                <div class="summary-row"><span>Shipping</span><span style="color:var(--forest);">Calculated at checkout</span></div>
                <div class="summary-total"><span>Total</span><span style="color:var(--forest);">R<?php echo number_format($total, 2); ?></span></div>

                <?php if ($is_logged_in): ?>
                    <a href="checkout.php" class="btn-checkout" style="display:block;text-align:center;">Proceed to Checkout →</a>
                <?php else: ?>
                    <div style="background:#fff8f0;border:1px solid #f0d5b0;border-radius:10px;padding:1rem;text-align:center;margin-top:1rem;">
                        <div style="font-size:1.3rem;margin-bottom:0.4rem;">🔒</div>
                        <p style="font-size:0.88rem;color:var(--mid);margin-bottom:0.75rem;font-weight:500;">Login or create an account to purchase items</p>
                        <a href="login.php" class="btn-checkout" style="display:block;text-align:center;margin-bottom:0.5rem;">Sign In</a>
                        <a href="register.php" style="display:block;text-align:center;font-size:0.82rem;color:var(--clay);text-decoration:none;font-weight:600;">Create Account →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php if ($is_admin_view): ?>
<div class="admin-shop-bar">
    <span style="color:rgba(255,255,255,0.5);">👑 Viewing as Super Admin</span>
    <a href="admin/dashboard.php" style="background:var(--clay);color:white;padding:0.45rem 1.2rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:0.82rem;">
        📊 Dashboard
    </a>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
