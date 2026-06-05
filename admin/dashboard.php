<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../db.php';

// Handle COD order confirmation (only for Cash on Delivery + pending)
if (isset($_GET['confirm_cod']) && is_numeric($_GET['confirm_cod'])) {
    $oid = intval($_GET['confirm_cod']);
    mysqli_query($conn, "UPDATE orders SET status='confirmed' WHERE order_id=$oid AND payment_method='Cash on Delivery' AND status='pending'");
    header("Location: dashboard.php?tab=orders");
    exit();
}

// Handle delete product
if (isset($_GET['delete_product']) && is_numeric($_GET['delete_product'])) {
    $pid = intval($_GET['delete_product']);
    $img_res = mysqli_query($conn, "SELECT product_image FROM products WHERE product_id=$pid");
    if ($img_row = mysqli_fetch_assoc($img_res)) {
        $img_path = dirname(__DIR__) . '/' . $img_row['product_image'];
        if (!empty($img_row['product_image']) && strpos($img_row['product_image'], 'uploads/') === 0 && file_exists($img_path)) {
            unlink($img_path);
        }
    }
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$pid");
    header("Location: dashboard.php?tab=products");
    exit();
}

// Handle delete user
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    if ($uid != $_SESSION['admin_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE user_id=$uid");
    }
    header("Location: dashboard.php?tab=users");
    exit();
}

// View shop as super admin: set role=admin in session but keep admin_id
if (isset($_GET['view_shop'])) {
    $_SESSION['user_id']   = $_SESSION['admin_id'];
    $_SESSION['full_name'] = $_SESSION['admin_name'];
    $_SESSION['role']      = 'admin'; // keeps admin privileges
    header("Location: ../index.php");
    exit();
}

// Stats
$u_res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM users"); $u_count = mysqli_fetch_assoc($u_res)['t'];
$p_res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM products"); $p_count = mysqli_fetch_assoc($p_res)['t'];
$o_res   = mysqli_query($conn, "SELECT COUNT(*) as t FROM orders"); $o_count = mysqli_fetch_assoc($o_res)['t'];
$rev_res = mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE status != 'cancelled'");
$revenue = mysqli_fetch_assoc($rev_res)['t'] ?? 0;

$users    = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$products = mysqli_query($conn, "SELECT p.*, u.full_name as seller_name FROM products p LEFT JOIN users u ON p.seller_id=u.user_id ORDER BY p.created_at DESC");
$orders   = mysqli_query($conn, "SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id=u.user_id ORDER BY o.created_at DESC LIMIT 20");

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <style>
        body { background: #f4f4f4; }
        .admin-wrapper { display: flex; min-height: calc(100vh - 72px); }
        .sidebar { width: 220px; flex-shrink: 0; background: #1a1a1a; }
        .main-content { flex: 1; padding: 2rem; overflow: auto; }
        .data-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); }
        .data-table table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .data-table th { background: #f8f8f8; padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--mid); border-bottom: 1px solid var(--sand); }
        .data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .badge-role { font-size: 0.7rem; padding: 3px 8px; border-radius: 50px; font-weight: 600; }
        .badge-admin  { background: #f8d7da; color: #721c24; }
        .badge-seller { background: #fff3cd; color: #856404; }
        .badge-buyer  { background: #d1ecf1; color: #0c5460; }
        .view-shop-btn { display: flex; align-items: center; gap: 0.5rem; background: #2d6a4f; color: white !important; padding: 0.6rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.82rem; font-weight: 600; margin: 0.5rem 0.75rem; transition: background 0.2s; }
        .view-shop-btn:hover { background: #1a7a4a; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">Re<span>Wear</span></a>
        <span style="color:rgba(255,255,255,0.4);font-size:0.82rem;">👑 Super Admin</span>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><span class="nav-link" style="color:rgba(255,255,255,0.6);">👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span></li>
            <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <div style="padding: 1.5rem 1rem 0.5rem;">
            <p style="color:rgba(255,255,255,0.3);font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Navigation</p>
        </div>
        <a href="dashboard.php" class="admin-nav-link <?php echo $tab==='overview'?'active':''; ?>">📊 Overview</a>
        <a href="dashboard.php?tab=products" class="admin-nav-link <?php echo $tab==='products'?'active':''; ?>">🏷 Products</a>
        <a href="dashboard.php?tab=users" class="admin-nav-link <?php echo $tab==='users'?'active':''; ?>">👥 Users</a>
        <a href="dashboard.php?tab=orders" class="admin-nav-link <?php echo $tab==='orders'?'active':''; ?>">📦 Orders</a>
        <div style="border-top:1px solid rgba(255,255,255,0.07);margin:1rem 0;"></div>
        <!-- View Shop keeps admin session intact -->
        <a href="dashboard.php?view_shop=1" class="view-shop-btn">🛍 View Shop</a>
        <p style="color:rgba(255,255,255,0.25);font-size:0.65rem;padding:0 1rem;margin:0.25rem 0 0;">Stays logged in as super admin</p>
    </div>

    <!-- Main -->
    <div class="main-content">

        <?php if ($tab === 'overview'): ?>
        <h4 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Overview</h4>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon blue">👥</div>
                    <div>
                        <div style="font-size:0.75rem;font-weight:600;color:var(--light-mid);text-transform:uppercase;letter-spacing:0.6px;">Users</div>
                        <div style="font-size:1.8rem;font-family:'Playfair Display',serif;font-weight:700;"><?php echo $u_count; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon yellow">🏷</div>
                    <div>
                        <div style="font-size:0.75rem;font-weight:600;color:var(--light-mid);text-transform:uppercase;letter-spacing:0.6px;">Products</div>
                        <div style="font-size:1.8rem;font-family:'Playfair Display',serif;font-weight:700;"><?php echo $p_count; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon green">📦</div>
                    <div>
                        <div style="font-size:0.75rem;font-weight:600;color:var(--light-mid);text-transform:uppercase;letter-spacing:0.6px;">Orders</div>
                        <div style="font-size:1.8rem;font-family:'Playfair Display',serif;font-weight:700;"><?php echo $o_count; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon red">💰</div>
                    <div>
                        <div style="font-size:0.75rem;font-weight:600;color:var(--light-mid);text-transform:uppercase;letter-spacing:0.6px;">Revenue</div>
                        <div style="font-size:1.8rem;font-family:'Playfair Display',serif;font-weight:700;">R<?php echo number_format($revenue, 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <h6 style="font-weight:700;margin-bottom:1rem;">Quick Links</h6>
        <div class="d-flex gap-2 flex-wrap">
            <a href="dashboard.php?tab=products" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.55rem 1.2rem;">Manage Products</a>
            <a href="dashboard.php?tab=users" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.55rem 1.2rem;background:var(--mid);">Manage Users</a>
            <a href="dashboard.php?tab=orders" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.55rem 1.2rem;background:var(--forest);">View Orders</a>
            <a href="dashboard.php?view_shop=1" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.55rem 1.2rem;background:#2d6a4f;">🛍 View Shop</a>
        </div>

        <?php elseif ($tab === 'products'): ?>
        <h4 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Products</h4>
        <div class="data-table">
            <table>
                <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Seller</th><th>Condition</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($p['product_image'] ?: ''); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;background:#eee;" alt=""></td>
                    <td style="max-width:180px;font-weight:500;"><?php echo htmlspecialchars($p['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                    <td style="font-weight:700;color:var(--forest);">R<?php echo number_format($p['product_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($p['seller_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($p['condition_type']); ?></td>
                    <td><a href="dashboard.php?delete_product=<?php echo $p['product_id']; ?>" class="btn-remove" onclick="return confirm('Delete this product?')">Delete</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($tab === 'users'): ?>
        <h4 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Users</h4>
        <div class="data-table">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td style="color:var(--light-mid);"><?php echo $u['user_id']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge-role badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['user_id'] != $_SESSION['admin_id']): ?>
                        <a href="dashboard.php?delete_user=<?php echo $u['user_id']; ?>&tab=users" class="btn-remove" onclick="return confirm('Delete this user?')">Delete</a>
                        <?php else: ?><span style="font-size:0.75rem;color:var(--light-mid);">You</span><?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($tab === 'orders'): ?>
        <h4 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Recent Orders</h4>
        <div class="data-table">
            <table>
                <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($o = mysqli_fetch_assoc($orders)): 
                    $is_cod_pending = ($o['payment_method'] === 'Cash on Delivery' && $o['status'] === 'pending');
                ?>
                <tr>
                    <td style="font-weight:700;">#<?php echo $o['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($o['full_name'] ?? 'Guest'); ?></td>
                    <td style="font-weight:700;color:var(--forest);">R<?php echo number_format($o['total_amount'], 2); ?></td>
                    <td>
                        <?php echo htmlspecialchars($o['payment_method']); ?>
                        <?php if ($o['payment_method'] === 'Cash on Delivery'): ?>
                            <span style="display:inline-block;background:#fff3e0;color:#7c4a03;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:3px;">COD</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $sc = $o['status'];
                        $colors = ['pending'=>'#856404','confirmed'=>'#155724','shipped'=>'#0c5460','delivered'=>'#2d6a4f','cancelled'=>'#721c24'];
                        $bg = ['pending'=>'#fff3cd','confirmed'=>'#d4edda','shipped'=>'#d1ecf1','delivered'=>'#e8f5e9','cancelled'=>'#f8d7da'];
                        ?>
                        <span style="font-size:0.75rem;font-weight:700;color:<?php echo $colors[$sc] ?? '#555'; ?>;background:<?php echo $bg[$sc] ?? '#eee'; ?>;padding:2px 8px;border-radius:20px;">
                            <?php echo ucfirst($sc); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                    <td>
                        <?php if ($is_cod_pending): ?>
                        <a href="dashboard.php?confirm_cod=<?php echo $o['order_id']; ?>&tab=orders"
                           style="display:inline-block;background:var(--forest);color:white;font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:6px;text-decoration:none;"
                           onclick="return confirm('Mark COD order #<?php echo $o['order_id']; ?> as confirmed?')">
                            ✓ Confirm
                        </a>
                        <?php else: ?>
                        <span style="font-size:0.75rem;color:var(--light-mid);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
