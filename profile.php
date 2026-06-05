<?php
session_start();
include 'db.php';
// Sellers stay on their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    header("Location: seller-dashboard.php");
    exit();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile.php&msg=login_required");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id");
$user = mysqli_fetch_assoc($user_res);

// Orders PLACED (as buyer)
$bought_res = mysqli_query($conn, "SELECT COUNT(*) as cnt, SUM(total_amount) as spent FROM orders WHERE user_id=$user_id AND status IN ('confirmed','shipped','delivered')");
$bought = mysqli_fetch_assoc($bought_res);

// Sales RECEIVED (as seller) — count confirmed order items for their products
$is_seller = ($user['role'] === 'seller' || $user['role'] === 'admin');
$sales_count = 0;
$revenue = 0;
if ($is_seller) {
    $sales_res = mysqli_query($conn, "
        SELECT COUNT(oi.item_id) as cnt, SUM(oi.price) as rev
        FROM order_items oi
        JOIN products p ON p.product_id = oi.product_id
        JOIN orders o ON o.order_id = oi.order_id
        WHERE p.seller_id = $user_id AND o.status IN ('confirmed','shipped','delivered')
    ");
    $sales_row = mysqli_fetch_assoc($sales_res);
    $sales_count = intval($sales_row['cnt']);
    $revenue     = floatval($sales_row['rev']);
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$member_since = date('F Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-avatar {
            width: 72px; height: 72px;
            background: var(--forest); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: white; font-family: 'Playfair Display', serif;
            font-weight: 700; flex-shrink: 0;
        }
        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.85rem 0; border-bottom: 1px solid var(--sand); font-size: 0.9rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--light-mid); font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .info-value { color: var(--dark); font-weight: 500; }
        .stat-box {
            background: white; border-radius: 12px; padding: 1.2rem;
            text-align: center; box-shadow: var(--shadow);
        }
        .stat-num { font-family: 'Playfair Display', serif; font-size: 1.7rem; font-weight: 700; color: var(--forest); }
        .stat-num.clay { color: var(--clay); }
        .stat-lbl { font-size: 0.73rem; color: var(--light-mid); margin-top: 0.2rem; }
        .section-head { font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--light-mid); margin: 1.5rem 0 0.75rem; }
        .tab-nav { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--sand); }
        .tab-nav a {
            padding: 0.6rem 1.4rem; border-radius: 8px 8px 0 0;
            font-size: 0.88rem; font-weight: 600; text-decoration: none;
            color: var(--mid); border: 2px solid transparent;
            border-bottom: none; margin-bottom: -2px; transition: all 0.15s;
        }
        .tab-nav a.active { background: white; border-color: var(--sand); border-bottom-color: white; color: var(--forest); }
        .tab-nav a:hover:not(.active) { color: var(--forest); background: rgba(0,0,0,0.03); }
        .role-chip { display: inline-block; padding: 0.2rem 0.7rem; border-radius: 20px; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em; }
        .role-buyer  { background: #e8f5e9; color: #2d6a4f; }
        .role-seller { background: #fff3e0; color: #7c4a03; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if ($is_seller): ?>
                <li class="nav-item"><a class="nav-link" href="seller-dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="cart.php">🛒 <span class="cart-badge"><?php echo $cart_count; ?></span></a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container"><h1>My Account</h1></div>
</div>

<div class="container mb-5">
    <div class="tab-nav">
        <a href="orders.php">📦 My Orders</a>
        <a href="profile.php" class="active">👤 Profile</a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-box">
                <div class="stat-num"><?php echo $bought['cnt'] ?: 0; ?></div>
                <div class="stat-lbl">Orders Placed</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-box">
                <div class="stat-num">R<?php echo $bought['spent'] ? number_format($bought['spent'], 0) : '0'; ?></div>
                <div class="stat-lbl">Total Spent</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-box">
                <div class="stat-num"><?php echo $cart_count; ?></div>
                <div class="stat-lbl">In Cart</div>
            </div>
        </div>
    </div>

    <!-- Profile card -->
    <div class="dash-card">
        <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:1.5rem;">
            <div class="profile-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
            <div>
                <div style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div style="font-size:0.8rem;color:var(--light-mid);">Member since <?php echo $member_since; ?></div>
                <span class="role-chip role-<?php echo $user['role'] === 'admin' ? 'seller' : $user['role']; ?> mt-1">
                    <?php
                    if ($user['role'] === 'seller') echo '🏷 Seller';
                    elseif ($user['role'] === 'admin') echo '👑 Admin';
                    else echo '🛍 Buyer';
                    ?>
                </span>
            </div>
        </div>

        <div class="info-row">
            <span class="info-label">Full Name</span>
            <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email Address</span>
            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Account Role</span>
            <span class="info-value"><?php echo ucfirst($user['role']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Member Since</span>
            <span class="info-value"><?php echo date('d F Y', strtotime($user['created_at'])); ?></span>
        </div>
    </div>

    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1rem;">
        <a href="orders.php" style="display:inline-flex;align-items:center;gap:0.4rem;background:var(--forest);color:white;padding:0.65rem 1.3rem;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:600;">📦 My Orders</a>
        <a href="index.php" style="display:inline-flex;align-items:center;gap:0.4rem;background:white;color:var(--forest);border:1.5px solid var(--forest);padding:0.65rem 1.3rem;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:600;">🛍 Shop</a>
        <a href="logout.php" style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff5f5;color:#c0392b;border:1.5px solid #f5c6c6;padding:0.65rem 1.3rem;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:600;">← Sign Out</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
