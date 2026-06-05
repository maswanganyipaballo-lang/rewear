<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=seller-dashboard.php");
    exit();
}

if ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error   = '';

// Add Product with image upload
if (isset($_POST['add_product'])) {
    $product_name        = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $product_price       = floatval($_POST['product_price']);
    $product_description = mysqli_real_escape_string($conn, trim($_POST['product_description']));
    $category            = mysqli_real_escape_string($conn, $_POST['category']);
    $condition_type      = mysqli_real_escape_string($conn, $_POST['condition_type']);
    $size                = mysqli_real_escape_string($conn, trim($_POST['size']));
    $seller_id           = intval($_SESSION['user_id']);
    $product_image       = '';

    // Handle image upload
    if (isset($_FILES['product_image_file']) && $_FILES['product_image_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type     = mime_content_type($_FILES['product_image_file']['tmp_name']);
        $file_size     = $_FILES['product_image_file']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPEG, PNG, WebP, and GIF images are allowed.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $error = "Image must be smaller than 5MB.";
        } else {
            $ext       = pathinfo($_FILES['product_image_file']['name'], PATHINFO_EXTENSION);
            $filename  = 'product_' . $seller_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($_FILES['product_image_file']['tmp_name'], $upload_dir . $filename)) {
                $product_image = 'uploads/' . $filename;
            } else {
                $error = "Failed to upload image. Please try again.";
            }
        }
    } elseif (!empty(trim($_POST['product_image_url']))) {
        // Fallback: URL
        $product_image = mysqli_real_escape_string($conn, trim($_POST['product_image_url']));
    }

    if (empty($error)) {
        if (empty($product_name) || $product_price <= 0) {
            $error = "Product name and a valid price are required.";
        } else {
            $product_image_safe = mysqli_real_escape_string($conn, $product_image);
            $sql = "INSERT INTO products (seller_id, product_name, product_price, product_image, product_description, category, condition_type, size)
                    VALUES ($seller_id, '$product_name', $product_price, '$product_image_safe', '$product_description', '$category', '$condition_type', '$size')";
            if (mysqli_query($conn, $sql)) {
                $success = "Product listed successfully! ✓";
            } else {
                $error = "Failed to add product. Please try again.";
            }
        }
    }
}

// Delete product (only own products)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id    = intval($_GET['delete']);
    $seller_id = intval($_SESSION['user_id']);
    // Also remove uploaded image file
    $img_res = mysqli_query($conn, "SELECT product_image FROM products WHERE product_id=$del_id AND seller_id=$seller_id");
    if ($img_row = mysqli_fetch_assoc($img_res)) {
        $img_path = __DIR__ . '/' . $img_row['product_image'];
        if (!empty($img_row['product_image']) && strpos($img_row['product_image'], 'uploads/') === 0 && file_exists($img_path)) {
            unlink($img_path);
        }
    }
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$del_id AND seller_id=$seller_id");
    header("Location: seller-dashboard.php");
    exit();
}

$seller_id    = intval($_SESSION['user_id']);
$my_products  = mysqli_query($conn, "
    SELECT p.*,
           COUNT(oi.item_id) as sales_count
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.product_id
    LEFT JOIN orders o ON o.order_id = oi.order_id AND o.status IN ('confirmed','shipped','delivered')
    WHERE p.seller_id = $seller_id
    GROUP BY p.product_id
    ORDER BY p.created_at DESC
");
$product_count = mysqli_num_rows($my_products);
mysqli_data_seek($my_products, 0);

$categories = ['Jackets', 'Dresses', 'Tops', 'Bottoms', 'Shoes', 'Coats', 'Accessories', 'Other'];
$conditions = ['New', 'Like New', 'Good', 'Fair'];

// Is this admin viewing as seller?
$is_admin_view = (isset($_SESSION['admin_id']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .upload-area { border: 2px dashed var(--sand); border-radius: 10px; padding: 1.2rem; text-align: center; cursor: pointer; background: #fafaf8; transition: border-color 0.2s; margin-bottom: 0.5rem; }
        .upload-area:hover { border-color: var(--clay); }
        .upload-area input[type=file] { display: none; }
        #imagePreview { max-width: 100%; max-height: 140px; border-radius: 8px; margin-top: 0.5rem; display: none; }
        <?php if ($is_admin_view): ?>
        .admin-shop-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #1a1a1a; color: white; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: center; gap: 1rem; z-index: 9999; font-size: 0.85rem; }
        body { padding-bottom: 60px; }
        <?php endif; ?>
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
        <ul class="navbar-nav ms-auto gap-1">
            <?php if (!$is_admin_view): ?>
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="dashboard-header">
    <div class="container">
        <h1>Seller Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">

        <!-- Left: Add product form -->
        <div class="col-md-5">
            <div class="dash-card">
                <h5>List a New Item</h5>

                <?php if ($success): ?>
                    <div class="alert-custom alert-success-custom"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert-custom alert-error-custom"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <label class="form-label-custom">Product Name *</label>
                    <input type="text" name="product_name" class="form-control-custom" placeholder="e.g. Vintage Levi's Jacket" required>

                    <label class="form-label-custom">Price (R) *</label>
                    <input type="number" step="0.01" min="1" name="product_price" class="form-control-custom" placeholder="e.g. 350" required>

                    <label class="form-label-custom">Category</label>
                    <select name="category" class="form-control-custom">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label-custom">Condition</label>
                    <select name="condition_type" class="form-control-custom">
                        <?php foreach ($conditions as $cond): ?>
                            <option value="<?php echo $cond; ?>"><?php echo $cond; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label-custom">Size</label>
                    <input type="text" name="size" class="form-control-custom" placeholder="e.g. M, L, 32, 9">

                    <label class="form-label-custom">Product Image</label>
                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('imageFileInput').click()">
                        <input type="file" id="imageFileInput" name="product_image_file" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this)">
                        <div id="uploadPrompt">
                            <div style="font-size:2rem;">📷</div>
                            <p style="margin:0.3rem 0 0;font-size:0.82rem;color:var(--mid);">Click to upload from your device</p>
                            <p style="margin:0;font-size:0.72rem;color:var(--light-mid);">JPEG, PNG, WebP, GIF — max 5MB</p>
                        </div>
                        <img id="imagePreview" src="" alt="Preview">
                    </div>
                    <p style="font-size:0.75rem;color:var(--light-mid);text-align:center;margin-bottom:0.4rem;">— or paste an image URL —</p>
                    <input type="url" name="product_image_url" class="form-control-custom" placeholder="https://..." id="imageUrlInput">

                    <label class="form-label-custom">Description</label>
                    <textarea name="product_description" class="form-control-custom" rows="3" placeholder="Describe your item — condition, measurements, etc."></textarea>

                    <button type="submit" name="add_product" class="btn-primary-custom">List Item →</button>
                </form>
            </div>
        </div>

        <!-- Right: My listings -->
        <div class="col-md-7">
            <div class="dash-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h5 style="margin:0;">My Listings</h5>
                    <span style="font-size:0.82rem;color:var(--light-mid);"><?php echo $product_count; ?> item<?php echo $product_count !== 1 ? 's' : ''; ?></span>
                </div>

                <?php if ($product_count === 0): ?>
                    <div class="empty-state" style="padding:2rem 0;">
                        <div class="empty-icon">🏷</div>
                        <p>No listings yet. Add your first item!</p>
                    </div>
                <?php else: ?>
                    <?php while ($p = mysqli_fetch_assoc($my_products)): ?>
                    <div style="display:flex;align-items:center;gap:1rem;padding:0.9rem 0;border-bottom:1px solid var(--sand);">
                        <img src="<?php echo htmlspecialchars($p['product_image'] ?: 'https://via.placeholder.com/60'); ?>"
                             style="width:60px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($p['product_name']); ?>
                            </div>
                            <div style="font-size:0.78rem;color:var(--light-mid);">
                                <?php echo htmlspecialchars($p['category']); ?> ·
                                <?php echo htmlspecialchars($p['condition_type']); ?>
                            </div>
                            <?php if ($p['sales_count'] > 0): ?>
                            <div style="margin-top:0.25rem;">
                                <span style="display:inline-block;background:#d4edda;color:#155724;font-size:0.68rem;font-weight:700;padding:0.15rem 0.55rem;border-radius:20px;letter-spacing:0.03em;">
                                    ✓ <?php echo $p['sales_count']; ?> sale<?php echo $p['sales_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <div style="margin-top:0.25rem;">
                                <span style="display:inline-block;background:#f5f5f3;color:var(--light-mid);font-size:0.68rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:20px;">
                                    No sales yet
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-family:'Playfair Display',serif;font-weight:700;color:var(--forest);white-space:nowrap;">
                            R<?php echo number_format($p['product_price'], 2); ?>
                        </div>
                        <a href="seller-dashboard.php?delete=<?php echo $p['product_id']; ?>"
                           class="btn-remove"
                           onclick="return confirm('Remove this listing?')">Remove</a>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
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
<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const prompt  = document.getElementById('uploadPrompt');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            prompt.style.display  = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        // Clear URL field
        document.getElementById('imageUrlInput').value = '';
    }
}

// Drag & drop support
const area = document.getElementById('uploadArea');
area.addEventListener('dragover', e => { e.preventDefault(); area.style.borderColor = 'var(--clay)'; });
area.addEventListener('dragleave', () => { area.style.borderColor = ''; });
area.addEventListener('drop', e => {
    e.preventDefault();
    area.style.borderColor = '';
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        document.getElementById('imageFileInput').files = e.dataTransfer.files;
        previewImage(document.getElementById('imageFileInput'));
    }
});
</script>
</body>
</html>
