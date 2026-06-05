<?php
session_start();
include 'db.php';
// Sellers stay on their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'seller') {
    header("Location: seller-dashboard.php");
    exit();
}


// Must be logged in to checkout
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php&msg=login_required");
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) { header("Location: cart.php"); exit(); }

$total = 0;
$items = [];
foreach ($cart as $id) {
    $id      = intval($id);
    $res     = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $product = mysqli_fetch_assoc($res);
    if ($product) { $items[] = $product; $total += $product['product_price']; }
}

$cod_available = $total < 600;

// PayFast sandbox credentials
$merchant_id  = '10049456';
$merchant_key = '89zf9n0sa90ds';
$passphrase   = '';
$sandbox_url  = 'https://sandbox.payfast.co.za/eng/process';

$success = false;
$order_id_placed = null;
$error   = '';

function generatePayFastSignature($data, $passphrase = '') {
    $pfOutput = '';
    foreach ($data as $key => $val) {
        if ($val !== '') {
            $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
        }
    }
    $pfOutput = rtrim($pfOutput, '&');
    if ($passphrase !== '') {
        $pfOutput .= '&passphrase=' . urlencode(trim($passphrase));
    }
    return md5($pfOutput);
}

if (isset($_POST['place_order'])) {
    $address        = trim($_POST['shipping_address']);
    $payment_choice = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'payfast';

    // Validate COD eligibility
    if ($payment_choice === 'cod' && !$cod_available) {
        $payment_choice = 'payfast';
    }

    if (empty($address)) {
        $error = "Please enter a shipping address.";
    } else {
        $address_safe = mysqli_real_escape_string($conn, $address);
        $user_id      = intval($_SESSION['user_id']);

        if ($payment_choice === 'cod') {
            // Cash on Delivery — place order directly
            $sql = "INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, status)
                    VALUES ($user_id, $total, 'Cash on Delivery', '$address_safe', 'pending')";
            mysqli_query($conn, $sql);
            $order_id_placed = mysqli_insert_id($conn);
            foreach ($items as $product) {
                $pid   = intval($product['product_id']);
                $price = floatval($product['product_price']);
                mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, price) VALUES ($order_id_placed, $pid, $price)");
            }
            $_SESSION['cart'] = [];
            $_SESSION['cod_order_id'] = $order_id_placed;
            header("Location: cod-success.php");
            exit();
        } else {
            // PayFast flow
            $sql = "INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, status)
                    VALUES ($user_id, $total, 'PayFast', '$address_safe', 'pending')";
            mysqli_query($conn, $sql);
            $order_id_placed = mysqli_insert_id($conn);
            foreach ($items as $product) {
                $pid   = intval($product['product_id']);
                $price = floatval($product['product_price']);
                mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, price) VALUES ($order_id_placed, $pid, $price)");
            }
            $_SESSION['pending_order_id'] = $order_id_placed;
            $return_url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment-success.php';
            $cancel_url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment-cancel.php';
            $notify_url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment-notify.php';
            $pf_data = [
                'merchant_id'  => $merchant_id,
                'merchant_key' => $merchant_key,
                'return_url'   => $return_url,
                'cancel_url'   => $cancel_url,
                'notify_url'   => $notify_url,
                'name_first'   => explode(' ', $_SESSION['full_name'])[0],
                'name_last'    => trim(substr($_SESSION['full_name'], strpos($_SESSION['full_name'], ' '))),
                'email_address'=> '',
                'm_payment_id' => $order_id_placed,
                'amount'       => number_format($total, 2, '.', ''),
                'item_name'    => 'ReWear Order #' . $order_id_placed,
                'item_description' => count($items) . ' item(s) from ReWear',
            ];
            $pf_data['signature'] = generatePayFastSignature($pf_data, $passphrase);
            $_SESSION['payfast_data']    = $pf_data;
            $_SESSION['payfast_sandbox'] = $sandbox_url;
            $_SESSION['cart'] = [];
            header("Location: payfast-redirect.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — ReWear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .payment-option {
            border: 2px solid var(--sand);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .payment-option:hover { border-color: var(--forest); }
        .payment-option.selected { border-color: var(--forest); background: #f0f7f3; }
        .payment-option input[type=radio] { accent-color: var(--forest); width: 18px; height: 18px; flex-shrink: 0; }
        .payment-option .pay-label { font-weight: 600; font-size: 0.9rem; }
        .payment-option .pay-sub { font-size: 0.75rem; color: var(--light-mid); margin-top: 0.1rem; }
        .cod-badge { background: #fff3e0; color: #7c4a03; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 10px; font-weight: 700; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">Re<span>Wear</span></a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="cart.php">← Back to Cart</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container"><h1>Checkout</h1></div>
</div>

<div class="container mb-5">
    <?php if ($error): ?>
        <div class="alert-custom alert-error-custom mb-4"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-7">
            <form method="POST" id="checkoutForm">
                <div class="checkout-card">
                    <h5>Shipping Details</h5>
                    <label class="form-label-custom">Full Name</label>
                    <input type="text" name="full_name" class="form-control-custom"
                           value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                    <label class="form-label-custom">Shipping Address</label>
                    <textarea name="shipping_address" class="form-control-custom" rows="3"
                              placeholder="Street address, city, postal code" required></textarea>
                    <label class="form-label-custom">Phone Number</label>
                    <input type="tel" name="phone" class="form-control-custom" placeholder="+27 xxx xxx xxxx">
                </div>

                <div class="checkout-card">
                    <h5>Payment Method</h5>

                    <!-- PayFast option -->
                    <label class="payment-option selected" id="opt-payfast" onclick="selectPayment('payfast')">
                        <input type="radio" name="payment_method" value="payfast" checked>
                        <div>
                            <div class="pay-label">💳 PayFast Secure Payment</div>
                            <div class="pay-sub">Cards, EFT, SnapScan, Zapper & more · 🔒 Sandbox Mode</div>
                        </div>
                    </label>

                    <?php if ($cod_available): ?>
                    <!-- Cash on Delivery option (only if total < R600) -->
                    <label class="payment-option" id="opt-cod" onclick="selectPayment('cod')">
                        <input type="radio" name="payment_method" value="cod">
                        <div>
                            <div class="pay-label">🤝 Cash on Delivery <span class="cod-badge">Under R600</span></div>
                            <div class="pay-sub">Pay with cash when your order arrives at your door</div>
                        </div>
                    </label>
                    <?php else: ?>
                    <div style="background:#f8f8f6;border-radius:10px;padding:0.9rem 1rem;font-size:0.8rem;color:var(--light-mid);border:1.5px dashed var(--sand);">
                        💡 Cash on Delivery is only available for orders under R600.
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="place_order" class="btn-checkout" id="submitBtn">
                    Pay with PayFast · R<?php echo number_format($total, 2); ?> →
                </button>
            </form>
        </div>

        <div class="col-md-5">
            <div class="cart-summary">
                <h4>Order Summary</h4>
                <?php foreach ($items as $product): ?>
                    <div class="summary-row">
                        <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </span>
                        <span>R<?php echo number_format($product['product_price'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-total">
                    <span>Total</span>
                    <span style="color:var(--forest);">R<?php echo number_format($total, 2); ?></span>
                </div>
                <?php if ($cod_available): ?>
                <div style="font-size:0.75rem;color:var(--light-mid);margin-top:0.5rem;text-align:center;">✓ Cash on Delivery available for this order</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectPayment(method) {
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
    const btn = document.getElementById('submitBtn');
    if (method === 'cod') {
        document.getElementById('opt-cod').classList.add('selected');
        btn.textContent = 'Place Order · Cash on Delivery →';
    } else {
        document.getElementById('opt-payfast').classList.add('selected');
        btn.textContent = 'Pay with PayFast · R<?php echo number_format($total, 2); ?> →';
    }
}
</script>
</body>
</html>
