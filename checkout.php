<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) redirect('login.php?redirect=checkout.php');

$uid = (int)$_SESSION['user_id'];

// Get user info
$us = $conn->prepare("SELECT * FROM users WHERE id = ?");
$us->bind_param('i', $uid);
$us->execute();
$user = $us->get_result()->fetch_assoc();
$us->close();

// Get cart
$cs = $conn->prepare("
    SELECT c.quantity, p.id AS pid, p.name, p.price, p.original_price, p.stock,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
    FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?
");
$cs->bind_param('i', $uid);
$cs->execute();
$cart_items = $cs->get_result()->fetch_all(MYSQLI_ASSOC);
$cs->close();

if (empty($cart_items)) redirect('cart.php');

$subtotal = 0;
$saving   = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    if ($item['original_price'] > $item['price']) {
        $saving += ($item['original_price'] - $item['price']) * $item['quantity'];
    }
}
$shipping  = ($subtotal > 999) ? 0 : 99;
$grand     = $subtotal + $shipping;

$error = '';

// ── Place order ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s_name    = trim($_POST['s_name']    ?? '');
    $s_phone   = trim($_POST['s_phone']   ?? '');
    $s_address = trim($_POST['s_address'] ?? '');
    $s_city    = trim($_POST['s_city']    ?? '');
    $s_state   = trim($_POST['s_state']   ?? '');
    $s_pincode = trim($_POST['s_pincode'] ?? '');
    $payment   = trim($_POST['payment']   ?? 'COD');

    if (!$s_name || !$s_phone || !$s_address || !$s_city || !$s_state || !$s_pincode) {
        $error = 'Please fill in all shipping details.';
    } else {
        $order_no = generateOrderNumber();

        $ins = $conn->prepare("INSERT INTO orders (order_number, user_id, total_amount, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode, payment_method) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ins->bind_param('sidsssssss', $order_no, $uid, $grand, $s_name, $s_phone, $s_address, $s_city, $s_state, $s_pincode, $payment);
        $ins->execute();
        $order_id = $conn->insert_id;
        $ins->close();

        // Insert order items
        foreach ($cart_items as $item) {
            $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
            $oi->bind_param('iiid', $order_id, $item['pid'], $item['quantity'], $item['price']);
            $oi->execute();
            $oi->close();
        }

        // Clear cart
        $cl = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $cl->bind_param('i', $uid);
        $cl->execute();
        $cl->close();

        redirect("checkout.php?order_placed=1&order_no=$order_no&order_id=$order_id");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">

<?php if (isset($_GET['order_placed'])): ?>
<!-- ── Order Confirmation ──────────────────────────────────── -->
<div class="order-confirm-box">
  <div class="confirm-icon"><i class="fas fa-check"></i></div>
  <h2 class="fw-700 mb-2">Order Placed Successfully!</h2>
  <p class="text mb-4">Your order <strong class="text-warning"><?= e($_GET['order_no']) ?></strong> has been placed and is being processed.</p>
  <div class="d-flex justify-content-center gap-3 flex-wrap">
    <a href="user/orders.php" class="btn btn-warning px-4"><i class="fas fa-box me-2"></i>View My Orders</a>
    <a href="index.php" class="btn btn-outline-light px-4"><i class="fas fa-home me-2"></i>Continue Shopping</a>
  </div>

  <!-- Timeline -->
  <div class="mt-5 text-start" style="max-width:500px;margin:0 auto;">
    <h6 class="text-uppercase small mb-3">What happens next?</h6>
    <?php foreach (['Order Confirmed – We\'ve received your order', 'Processing – Your order is being packed', 'Shipped – Your order is on the way', 'Delivered – Enjoy your purchase!'] as $i => $step): ?>
    <div class="d-flex gap-3 mb-3">
      <div style="width:28px;height:28px;border-radius:50%;background:<?= $i===0?'var(--accent)':'rgba(255,255,255,0.1)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.75rem;font-weight:700;">
        <?= $i + 1 ?>
      </div>
      <div class="pt-1 <?= $i===0?'text-warning':'text' ?>"><?= $step ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php else: ?>
<!-- ── Checkout Form ──────────────────────────────────────── -->
<div class="es-breadcrumb"><a href="index.php">Home</a> &rsaquo; <a href="cart.php">Cart</a> &rsaquo; <span class="current">Checkout</span></div>
<h2 class="mb-4">Checkout</h2>

<?php if ($error): ?>
  <div class="alert-custom alert-error es-flash"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
<?php endif; ?>

<form method="POST">
<div class="row g-4">
  <!-- Shipping info -->
  <div class="col-lg-7">
    <div class="checkout-card">
      <h5 class="fw-700 mb-4"><i class="fas fa-map-marker-alt me-2 text-accent"></i>Shipping Address</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="s_name" class="form-control" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone *</label>
          <input type="tel" name="s_phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Address *</label>
          <textarea name="s_address" class="form-control" rows="2" required><?= e($user['address'] ?? '') ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">City *</label>
          <input type="text" name="s_city" class="form-control" value="<?= e($user['city'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">State *</label>
          <select name="s_state" class="form-select" required>
            <option value="">Select State</option>
            <?php foreach (['Andhra Pradesh','Delhi','Gujarat','Karnataka','Kerala','Maharashtra','Rajasthan','Tamil Nadu','Telangana','Uttar Pradesh','West Bengal'] as $st): ?>
            <option value="<?= $st ?>" <?= ($user['state'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Pincode *</label>
          <input type="text" name="s_pincode" class="form-control" maxlength="6" value="<?= e($user['pincode'] ?? '') ?>" required>
        </div>
      </div>
    </div>

    <!-- Payment method -->
    <div class="checkout-card">
      <h5 class="fw-700 mb-4"><i class="fas fa-credit-card me-2 text-accent"></i>Payment Method</h5>
      <label class="pay-option">
        <input type="radio" name="payment" value="COD" checked>
        <i class="fas fa-money-bill-wave text-success fa-lg"></i>
        <div><div class="fw-600">Cash on Delivery</div><small class="text-small">Pay when your order arrives</small></div>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment" value="UPI">
        <i class="fas fa-mobile-alt text-primary fa-lg"></i>
        <div><div class="fw-600">UPI Payment</div><small class="text-small">Pay using any UPI app</small></div>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment" value="Card">
        <i class="fas fa-credit-card text-accent fa-lg"></i>
        <div><div class="fw-600">Credit / Debit Card</div><small class="text-small">Visa, Mastercard, RuPay</small></div>
      </label>
    </div>
  </div>

  <!-- Order summary -->
  <div class="col-lg-5">
    <div class="order-summary-card">
      <h5 class="fw-700 mb-4">Order Summary</h5>

      <?php foreach ($cart_items as $item): ?>
      <div class="d-flex gap-3 mb-3 align-items-center">
        <img src="uploads/product_images/<?= e($item['img'] ?? 'placeholder.png') ?>"
             width="54" height="54" style="object-fit:contain;background:rgba(255,255,255,0.03);border-radius:8px;padding:4px;"
             onerror="this.src='https://via.placeholder.com/54x54/1e293b/64748b?text=?'">
        <div class="flex-grow-1">
          <div class="small fw-600"><?= e(substr($item['name'], 0, 35)) ?><?= strlen($item['name']) > 35 ? '...' : '' ?></div>
          <div class="text small">Qty: <?= $item['quantity'] ?></div>
        </div>
        <div class="fw-600"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
      </div>
      <?php endforeach; ?>

      <hr style="border-color:var(--border);">
      <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
      <?php if ($saving > 0): ?>
      <div class="summary-row saving"><span>Discount</span><span>−<?= formatPrice($saving) ?></span></div>
      <?php endif; ?>
      <div class="summary-row"><span>Shipping</span><span><?= $shipping === 0 ? '<span class="text-success">FREE</span>' : formatPrice($shipping) ?></span></div>
      <div class="summary-row total"><span>Total</span><span class="text-accent"><?= formatPrice($grand) ?></span></div>

      <button type="submit" class="btn-primary-custom mt-4 py-3">
        <i class="fas fa-check-circle me-2"></i>Place Order
      </button>
      <div class="text-center text small mt-3">
        <i class="fas fa-lock me-1"></i> Secure & encrypted checkout
      </div>
    </div>
  </div>
</div>
</form>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
