<?php
session_start();
include 'db.php';
// Sellers stay on their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    header("Location: seller-dashboard.php");
    exit();
}


$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search    = isset($_GET['search'])   ? mysqli_real_escape_string($conn, $_GET['search'])   : '';

$where = "WHERE 1=1";
if ($category) $where .= " AND category='$category'";
if ($search)   $where .= " AND (product_name LIKE '%$search%' OR product_description LIKE '%$search%')";

$result = mysqli_query($conn, "SELECT * FROM products $where ORDER BY created_at DESC");

$cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
$categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row['category'];
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReWear — Sustainable Second-Hand Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <?php if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <style>.admin-shop-bar{position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:white;padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:center;gap:1rem;z-index:9999;font-size:.85rem;}body{padding-bottom:60px;}</style>
    <?php endif; ?>

    <?php if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <style>.admin-shop-bar{position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:white;padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:center;gap:1rem;z-index:9999;font-size:.85rem;}body{padding-bottom:60px;}</style>
    <?php endif; ?>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <form class="d-flex ms-auto me-3" method="GET" action="index.php">
                <input class="form-control form-control-sm" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.15);color:white;min-width:200px;" type="search" name="search" placeholder="Search clothes..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-sm ms-1" style="background:var(--clay);color:white;border:none;" type="submit">🔍</button>
            </form>

            <ul class="navbar-nav align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="seller-dashboard.php">Sell</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">🛒 <span class="cart-badge"><?php echo $cart_count; ?></span></a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php" style="background:var(--clay);color:white!important;border-radius:6px;padding:0.4rem 0.9rem!important;">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="container text-center hero-content">
        <p class="section-label" style="color:var(--clay);letter-spacing:3px;">♻ Sustainable Fashion</p>
        <h1 class="fw-bold">Give Clothes<br>a Second Life</h1>
        <p class="subtitle">Buy and sell pre-loved fashion. Good for your wallet, great for the planet.</p>
        <a href="#products" class="btn-hero">Browse Now</a>

        <div class="hero-stats">
            <div class="hero-stat"><div class="num">500+</div><div class="lbl">Listings</div></div>
            <div class="hero-stat"><div class="num">2k+</div><div class="lbl">Members</div></div>
            <div class="hero-stat"><div class="num">100%</div><div class="lbl">Sustainable</div></div>
        </div>
    </div>
</section>

<!-- TRUST BANNER -->
<section class="trust-section">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-3 trust-item">
                <div class="icon">🔒</div>
                <h6>Secure Payments</h6>
                <p>Your info is always protected</p>
            </div>
            <div class="col-6 col-md-3 trust-item">
                <div class="icon">🚚</div>
                <h6>Nationwide Delivery</h6>
                <p>Delivered to your door</p>
            </div>
            <div class="col-6 col-md-3 trust-item">
                <div class="icon">♻</div>
                <h6>Eco Friendly</h6>
                <p>Reduce fashion waste</p>
            </div>
            <div class="col-6 col-md-3 trust-item">
                <div class="icon">💬</div>
                <h6>Verified Sellers</h6>
                <p>Community-trusted sellers</p>
            </div>
        </div>
    </div>
</section>

<!-- PRODUCTS SECTION -->
<section id="products" class="products-section">
    <div class="container">

        <div class="text-center mb-4">
            <p class="section-label">Explore</p>
            <h2 class="section-title">
                <?php if ($search): ?>
                    Results for "<?php echo htmlspecialchars($search); ?>"
                <?php elseif ($category): ?>
                    <?php echo htmlspecialchars($category); ?>
                <?php else: ?>
                    Featured Listings
                <?php endif; ?>
            </h2>
        </div>

        <!-- Category pills -->
        <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
            <a href="index.php" class="category-pill <?php echo !$category ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?php echo urlencode($cat); ?>" 
                   class="category-pill <?php echo $category === $cat ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Products grid -->
        <div class="row g-4">
            <?php
            $count = 0;
            while ($product = mysqli_fetch_assoc($result)):
                $count++;
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="product-card card">
                    <div class="img-wrap">
                        <img src="<?php echo htmlspecialchars($product['product_image'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>"
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             loading="lazy">
                        <?php if ($product['condition_type']): ?>
                            <span class="condition-badge"><?php echo htmlspecialchars($product['condition_type']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                        <div class="product-meta">
                            <?php echo $product['category'] ? htmlspecialchars($product['category']) : ''; ?>
                            <?php echo $product['size'] ? ' · Size ' . htmlspecialchars($product['size']) : ''; ?>
                        </div>
                        <div class="product-price">R<?php echo number_format($product['product_price'], 2); ?></div>
                        <a href="add-to-cart.php?id=<?php echo $product['product_id']; ?>"
                           class="btn-add-cart">Add to Cart 🛒</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if ($count === 0): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h5>No products found</h5>
                        <p>Try a different search or category.</p>
                        <a href="index.php" class="btn-add-cart" style="display:inline-block;width:auto;padding:0.55rem 1.5rem;">View All</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand">Re<span>Wear</span></div>
                <p>Sustainable second-hand fashion marketplace. Shop smart, live green.</p>
            </div>
            <div class="col-md-2">
                <h6>Shop</h6>
                <a href="index.php">All Items</a>
                <a href="index.php?category=Jackets">Jackets</a>
                <a href="index.php?category=Shoes">Shoes</a>
                <a href="index.php?category=Dresses">Dresses</a>
            </div>
            <div class="col-md-2">
                <h6>Sell</h6>
                <a href="seller-dashboard.php">List an Item</a>
                <a href="register.php">Create Account</a>
            </div>
            <div class="col-md-4">
                <h6>Contact</h6>
                <a href="#">hello@rewear.co.za</a>
                <a href="#">Instagram</a>
                <a href="#">WhatsApp</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 ReWear. All Rights Reserved.</span>
            <span>Made with ♻ in South Africa</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="admin-shop-bar">
    <span style="color:rgba(255,255,255,0.5);">👑 Viewing as Super Admin</span>
    <a href="admin/dashboard.php" style="background:var(--clay);color:white;padding:.45rem 1.2rem;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;">📊 Dashboard</a>
</div>
<?php endif; ?>
</body>
</html>
