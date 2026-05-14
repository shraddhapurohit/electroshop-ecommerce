<?php
require_once 'includes/db.php';

// ── AJAX actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => 'login.php']);
        exit;
    }

    $uid        = (int)$_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action     = $_POST['action'];

    if ($action === 'add') {
        // Check product exists and has stock
        $ps = $conn->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
        $ps->bind_param('i', $product_id);
        $ps->execute();
        $ps->bind_result($stock);
        $ps->fetch();
        $ps->close();

        if (!$stock) {
            echo json_encode(['success' => false, 'message' => 'Product not available']);
            exit;
        }

        // Insert or increment
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $ins->bind_param('ii', $uid, $product_id);
        $ins->execute();
        $ins->close();

        echo json_encode(['success' => true, 'message' => 'Added to cart!', 'cart_count' => getCartCount($conn, $uid)]);
        exit;
    }

    if ($action === 'update') {
        $change = (int)($_POST['change'] ?? 0);

        // Get current qty
        $cs = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $cs->bind_param('ii', $uid, $product_id);
        $cs->execute();
        $cs->bind_result($current_qty);
        $cs->fetch();
        $cs->close();

        $new_qty = $current_qty + $change;

        if ($new_qty <= 0) {
            // Remove
            $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $del->bind_param('ii', $uid, $product_id);
            $del->execute();
            $del->close();
            $removed = true;
        } else {
            $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $upd->bind_param('iii', $new_qty, $uid, $product_id);
            $upd->execute();
            $upd->close();
            $removed = false;
        }

        // Compute totals
        [$cart_total, $total_saving] = getCartTotals($conn, $uid);

        // Row total
        $rts = $conn->prepare("SELECT p.price FROM products p WHERE p.id = ?");
        $rts->bind_param('i', $product_id);
        $rts->execute();
        $rts->bind_result($price);
        $rts->fetch();
        $rts->close();

        echo json_encode([
            'success'      => true,
            'removed'      => $removed,
            'new_qty'      => $new_qty,
            'row_total'    => formatPrice($price * $new_qty),
            'cart_count'   => getCartCount($conn, $uid),
            'cart_total'   => $cart_total,
            'total_saving' => $total_saving,
        ]);
        exit;
    }

    if ($action === 'remove') {
        $del = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $del->bind_param('ii', $uid, $product_id);
        $del->execute();
        $del->close();
        echo json_encode(['success' => true, 'cart_count' => getCartCount($conn, $uid)]);
        exit;
    }
}

// ── Helper: cart totals ───────────────────────────────────────
function getCartTotals($conn, $uid) {
    $stmt = $conn->prepare("SELECT p.price, p.original_price, c.quantity FROM cart c JOIN products p ON p.id = c.product_id WHERE c.user_id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $total = 0; $original = 0;
    while ($r = $result->fetch_assoc()) {
        $total    += $r['price'] * $r['quantity'];
        $original += ($r['original_price'] ?: $r['price']) * $r['quantity'];
    }
    $saving = $original - $total;
    return [formatPrice($total), $saving > 0 ? formatPrice($saving) : null, $total];
}

// ── Page view ─────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    redirect('login.php?redirect=cart.php');
}

$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.quantity, p.id AS product_id, p.name, p.brand, p.price, p.original_price, p.stock, p.slug,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$subtotal  = 0;
$original_total = 0;
foreach ($cart_items as $item) {
    $subtotal       += $item['price'] * $item['quantity'];
    $original_total += ($item['original_price'] ?: $item['price']) * $item['quantity'];
}
$saving   = $original_total - $subtotal;
$shipping = ($subtotal > 999) ? 0 : 99;
$grand    = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping Cart – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
  <div class="es-breadcrumb"><a href="index.php">Home</a> &rsaquo; <span class="current">Shopping Cart</span></div>
  <h2 class="mb-4">Shopping Cart <span class="text fs-5 fw-normal">(<?= count($cart_items) ?> items)</span></h2>

  <?php if (empty($cart_items)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
      <h4>Your cart is empty</h4>
      <p class="text-small">Add products to your cart to see them here</p>
      <a href="products.php" class="btn btn-warning mt-3 px-4">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="row g-4">
    <!-- Cart items -->
    <div class="col-lg-8">
      <?php foreach ($cart_items as $item): ?>
      <div class="cart-item" id="cart-row-<?= $item['product_id'] ?>">
        <a href="product_details.php?slug=<?= e($item['slug']) ?>">
          <img class="cart-item-img"
               src="uploads/product_images/<?= e($item['img'] ?? 'placeholder.png') ?>"
               alt="<?= e($item['name']) ?>"
               onerror="this.src='https://via.placeholder.com/80x80/1e293b/64748b?text=?'">
        </a>
        <div class="flex-grow-1">
          <div class="cart-item-brand"><?= e($item['brand']) ?></div>
          <div class="cart-item-name">
            <a href="product_details.php?slug=<?= e($item['slug']) ?>" class="text-decoration-none text-white">
              <?= e($item['name']) ?>
            </a>
          </div>
          <div class="text small mb-2">
            <?= ($item['stock'] > 0) ? '<span class="text-success">In Stock</span>' : '<span class="text-danger">Out of Stock</span>' ?>
          </div>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="qty-control">
              <button class="qty-btn" onclick="updateCartQty(<?= $item['product_id'] ?>, -1)">−</button>
              <span class="qty-display" id="qty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span>
              <button class="qty-btn" onclick="updateCartQty(<?= $item['product_id'] ?>, 1)">+</button>
            </div>
            <div>
              <span class="fw-700 fs-5" id="row-total-<?= $item['product_id'] ?>"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
              <?php if ($item['original_price'] > $item['price']): ?>
                <span class="text small text-decoration-line-through ms-1"><?= formatPrice($item['original_price'] * $item['quantity']) ?></span>
              <?php endif; ?>
            </div>
            <button class="btn btn-sm btn-outline-danger ms-auto"
                    onclick="removeFromCart(<?= $item['product_id'] ?>)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Order summary -->
    <div class="col-lg-4">
      <div class="order-summary-card">
        <h5 class="fw-700 mb-4">Order Summary</h5>
        <div class="summary-row"><span>Subtotal (<?= count($cart_items) ?> items)</span><span><?= formatPrice($subtotal) ?></span></div>
        <?php if ($saving > 0): ?>
        <div class="summary-row saving"><span>You Save</span><span id="summary-saving">−<?= formatPrice($saving) ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $shipping === 0 ? '<span class="text-success">FREE</span>' : formatPrice($shipping) ?></span></div>
        <?php if ($shipping > 0): ?><div class="text small mb-2">Add <?= formatPrice(999 - $subtotal) ?> more for free shipping</div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><span id="summary-total"><?= formatPrice($grand) ?></span></div>
        <a href="checkout.php" class="btn-primary-custom d-block text-center mt-4 py-3">
          <i class="fas fa-lock me-2"></i>Proceed to Checkout
        </a>
        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
