<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) redirect('../login.php');
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// View single order detail
$view_order = null;
if (isset($_GET['view'])) {
    $oid_view = (int)$_GET['view'];
    $os = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $os->bind_param('ii', $oid_view, $uid);
    $os->execute();
    $view_order = $os->get_result()->fetch_assoc();
    $os->close();

    if ($view_order) {
        $items_s = $conn->prepare("
            SELECT oi.*, p.name, p.slug, p.brand,
                   (SELECT image FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) AS img
            FROM order_items oi JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $items_s->bind_param('i', $oid_view);
        $items_s->execute();
        $view_order['items'] = $items_s->get_result()->fetch_all(MYSQLI_ASSOC);
        $items_s->close();
    }
}

$status_colors = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Orders – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4">
  <div class="es-breadcrumb"><a href="../index.php">Home</a> &rsaquo; <span class="current">My Orders</span></div>
  <h2 class="mb-4">My Orders</h2>

  <?php if ($view_order): ?>
  <!-- Order detail view -->
  <div class="mb-3">
    <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Orders</a>
  </div>
  <div class="checkout-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
      <div>
        <h5 class="fw-700 mb-1">Order #<?= e($view_order['order_number']) ?></h5>
        <div class="text small"><?= date('d M Y, h:i A', strtotime($view_order['created_at'])) ?></div>
      </div>
      <span class="status-badge <?= $status_colors[$view_order['status']] ?>">
        <i class="fas fa-circle" style="font-size:8px;"></i> <?= ucfirst($view_order['status']) ?>
      </span>
    </div>

    <!-- Items -->
    <h6 class="text-uppercase small mb-3">Items Ordered</h6>
    <?php foreach ($view_order['items'] as $oi): ?>
    <div class="d-flex gap-3 mb-3 align-items-center">
      <img src="../uploads/product_images/<?= e($oi['img'] ?? 'placeholder.png') ?>"
           width="60" height="60" style="object-fit:contain;background:rgba(255,255,255,0.03);border-radius:8px;padding:4px;"
           onerror="this.src='https://via.placeholder.com/60x60/1e293b/64748b?text=?'">
      <div class="flex-grow-1">
        <div class="fw-600"><?= e($oi['name']) ?></div>
        <div class="text small"><?= e($oi['brand']) ?> &bull; Qty: <?= $oi['quantity'] ?></div>
      </div>
      <div class="fw-700"><?= formatPrice($oi['price'] * $oi['quantity']) ?></div>
    </div>
    <?php endforeach; ?>

    <hr style="border-color:var(--border);">
    <div class="row">
      <div class="col-md-6">
        <h6 class="text-uppercase small mb-2">Shipping Address</h6>
        <p class="small">
          <?= e($view_order['shipping_name']) ?><br>
          <?= e($view_order['shipping_address']) ?><br>
          <?= e($view_order['shipping_city']) ?>, <?= e($view_order['shipping_state']) ?> – <?= e($view_order['shipping_pincode']) ?><br>
          <?= e($view_order['shipping_phone']) ?>
        </p>
      </div>
      <div class="col-md-6">
        <h6 class="text-uppercase small mb-2">Payment</h6>
        <p class="small text"><?= e($view_order['payment_method']) ?></p>
        <div class="summary-row total" style="max-width:250px;">
          <span>Total Paid</span>
          <span class="text-accent"><?= formatPrice($view_order['total_amount']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <?php elseif (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-box-open"></i></div>
      <h4>No orders yet</h4>
      <p class="text">Your order history will appear here</p>
      <a href="../products.php" class="btn btn-warning mt-3 px-4">Start Shopping</a>
    </div>
  <?php else: ?>
  <div class="table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Date</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="text-warning fw-600"><?= e($o['order_number']) ?></td>
          <td class="text small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
          <td><?= $o['item_count'] ?> item<?= $o['item_count'] > 1 ? 's' : '' ?></td>
          <td class="fw-700"><?= formatPrice($o['total_amount']) ?></td>
          <td class="text small"><?= e($o['payment_method']) ?></td>
          <td><span class="status-badge <?= $status_colors[$o['status']] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td><a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-warning">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
