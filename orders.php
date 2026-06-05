<?php
session_start();
include 'db.php';
// Sellers stay on their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    header("Location: seller-dashboard.php");
    exit();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=orders.php&msg=login_required");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$orders_result = mysqli_query($conn, "
    SELECT o.*, COUNT(oi.item_id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = $user_id
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$orders = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $row;
}
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .order-card {
            background: white;
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--forest);
        }
        .order-card.cod { border-left-color: var(--clay); }
        .order-card.pending { border-left-color: #f0b429; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cod { background: #fde8d8; color: #7c3d12; }
        .order-meta { font-size: 0.8rem; color: var(--light-mid); margin-top: 0.3rem; }
        .tab-nav { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--sand); padding-bottom: 0; }
        .tab-nav a {
            padding: 0.6rem 1.4rem;
            border-radius: 8px 8px 0 0;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--mid);
            border: 2px solid transparent;
            border-bottom: none;
            margin-bottom: -2px;
            transition: all 0.15s;
        }
        .tab-nav a.active { background: white; border-color: var(--sand); border-bottom-color: white; color: var(--forest); }
        .tab-nav a:hover:not(.active) { color: var(--forest); background: rgba(0,0,0,0.03); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
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
        <a href="orders.php" class="active">📦 My Orders</a>
        <a href="profile.php">👤 Profile</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h4>No orders yet</h4>
            <p>When you place an order, it will appear here.</p>
            <a href="index.php" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.75rem 2rem;">Browse Items</a>
        </div>
    <?php else: ?>
        <p style="font-size:0.85rem;color:var(--light-mid);margin-bottom:1.2rem;"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?> placed</p>
        <?php foreach ($orders as $order):
            $status = $order['status'];
            $is_cod = ($order['payment_method'] === 'Cash on Delivery');
            $badge_class = $is_cod ? 'status-cod' : ($status === 'confirmed' ? 'status-confirmed' : 'status-pending');
            $card_class = $is_cod ? 'cod' : ($status === 'pending' ? 'pending' : '');
            $label = $is_cod ? 'Order Placed · Cash on Delivery' : ($status === 'confirmed' ? 'Order Confirmed · Paid' : ucfirst($status));
        ?>
        <div class="order-card <?php echo $card_class; ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.5rem;">
                <div>
                    <div style="font-weight:700;font-size:0.97rem;color:var(--dark);">Order #<?php echo $order['order_id']; ?></div>
                    <div class="order-meta">
                        <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?> ·
                        Placed <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                    </div>
                    <?php if ($order['shipping_address']): ?>
                    <div class="order-meta" style="margin-top:0.2rem;">📍 <?php echo htmlspecialchars($order['shipping_address']); ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.1rem;color:var(--forest);">
                        R<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                    <span class="status-badge <?php echo $badge_class; ?>"><?php echo $label; ?></span>
                    <?php if ($is_cod): ?>
                    <div style="font-size:0.72rem;color:var(--light-mid);margin-top:0.3rem;">Pay when your order arrives</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
