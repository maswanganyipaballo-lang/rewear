<?php
// PayFast ITN (Instant Transaction Notification) handler
include 'db.php';

$merchant_id  = '10049456';
$merchant_key = '89zf9n0sa90ds';
$passphrase   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pf_data = $_POST;
    $pf_payment_status = $pf_data['payment_status'] ?? '';
    $order_id = intval($pf_data['m_payment_id'] ?? 0);

    if ($pf_payment_status === 'COMPLETE' && $order_id > 0) {
        mysqli_query($conn, "UPDATE orders SET status='confirmed', payment_method='PayFast' WHERE order_id=$order_id");
    } elseif ($pf_payment_status === 'FAILED' && $order_id > 0) {
        mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE order_id=$order_id");
    }
    http_response_code(200);
}
