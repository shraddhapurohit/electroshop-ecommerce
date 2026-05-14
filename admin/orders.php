<?php
//  admin/orders.php

require_once '../includes/db.php';
require_once 'includes/sidebar.php';

$success = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed_status = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed_status)) {
        $upd = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $upd->bind_param('si', $status, $oid);
        $upd->execute(); $upd->close();
        $success = 'Order status updated!';
    }
}

// View single order
$view_order = null;
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $os  = $conn->prepare("SELECT o.*, u.name AS uname, u.email AS uemail FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $os->bind_param('i', $vid); $os->execute();
    $view_order = $os->get_result()->fetch_assoc(); $os->close();
    if ($view_order) {
        $is = $conn->prepare("SELECT oi.*, p.name, p.brand, (SELECT image FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS img FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
        $is->bind_param('i', $vid); $is->execute();
        $view_order['items'] = $is->get_result()->fetch_all(MYSQLI_ASSOC); $is->close();
    }
}

// Filters
$status_filter = trim($_GET['status'] ?? '');
$search        = trim($_GET['search'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$where  = ['1=1']; $types = ''; $params = [];
if ($status_filter) { $where[] = "o.status=?"; $params[] = $status_filter; $types .= 's'; }
if ($search)        { $where[] = "(o.order_number LIKE ? OR u.name LIKE ?)"; $like = "%$search%"; $params = array_merge($params, [$like,$like]); $types .= 'ss'; }
$where_str = implode(' AND ', $where);

// Count
$cnt = $conn->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id WHERE $where_str");
if ($params) $cnt->bind_param($types, ...$params);
$cnt->execute(); $cnt->bind_result($total); $cnt->fetch(); $cnt->close();
$total_pages = ceil($total / $per_page);

// Orders
$sql  = "SELECT o.*, u.name AS uname, COUNT(oi.id) AS item_count FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN order_items oi ON oi.order_id=o.id WHERE $where_str GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$ap   = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($types . 'ii', ...$ap);
$stmt->execute();
$orders = $stmt->get_result();

$status_colors = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
$status_list   = ['pending','processing','shipped','delivered','cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orders – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="admin-main">
  <h3 class="fw-700 mb-4">Orders Management</h3>

  <?php if ($success): ?><div class="alert-custom alert-success es-flash"><i class="fas fa-check-circle"></i> <?= e($success) ?></div><?php endif; ?>

  <?php if ($view_order): ?>
  <!-- ── Single Order Detail ─────────────────────────────── -->
  <div class="mb-3"><a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Orders</a></div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="checkout-card">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-4">
          <div>
            <h5 class="fw-700 mb-1">Order #<?= e($view_order['order_number']) ?></h5>
            <div class="text small"><?= date('d M Y, h:i A', strtotime($view_order['created_at'])) ?></div>
          </div>
          <form method="POST" class="d-flex align-items-center gap-2">
            <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
            <select name="status" class="form-select form-select-sm" style="background:var(--card-bg);border-color:var(--border);color:var(--text);">
              <?php foreach ($status_list as $s): ?>
              <option value="<?= $s ?>" <?= $view_order['status']===$s?'selected':''?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-warning fw-600 px-3">Update</button>
          </form>
        </div>

        <h6 class="text-uppercase small mb-3">Items</h6>
        <?php foreach ($view_order['items'] as $oi): ?>
        <div class="d-flex gap-3 mb-3 align-items-center">
          <img src="../uploads/product_images/<?= e($oi['img'] ?? 'placeholder.png') ?>"
               width="52" height="52" style="object-fit:contain;background:rgba(255,255,255,0.04);border-radius:8px;padding:4px;"
               onerror="this.src='https://via.placeholder.com/52x52/1e293b/64748b?text=?'">
          <div class="flex-grow-1">
            <div class="fw-600 small"><?= e($oi['name']) ?></div>
            <div class="text" style="font-size:0.72rem;"><?= e($oi['brand']) ?> · Qty: <?= $oi['quantity'] ?></div>
          </div>
          <div class="fw-700"><?= formatPrice($oi['price'] * $oi['quantity']) ?></div>
        </div>
        <?php endforeach; ?>

        <hr style="border-color:var(--border);">
        <div class="d-flex justify-content-between fw-700">
          <span>Total Amount</span>
          <span class="text-accent"><?= formatPrice($view_order['total_amount']) ?></span>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="checkout-card">
        <h6 class="fw-700 mb-3">Customer</h6>
        <div class="text small mb-1"><strong class="text-white"><?= e($view_order['uname']) ?></strong></div>
        <div class="text small mb-3"><?= e($view_order['uemail']) ?></div>

        <h6 class="fw-700 mb-2">Shipping Address</h6>
        <p class="text small">
          <?= e($view_order['shipping_name']) ?><br>
          <?= e($view_order['shipping_address']) ?><br>
          <?= e($view_order['shipping_city']) ?>, <?= e($view_order['shipping_state']) ?> – <?= e($view_order['shipping_pincode']) ?><br>
          <?= e($view_order['shipping_phone']) ?>
        </p>
        <div class="texts small">Payment: <strong class="text-white"><?= e($view_order['payment_method']) ?></strong></div>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- ── Orders Table ───────────────────────────────────── -->
  <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Order # or customer..." value="<?= e($search) ?>">
    <select name="status" class="form-select" style="max-width:160px;background:var(--card-bg);border-color:var(--border);color:var(--text);">
      <option value="">All Status</option>
      <?php foreach ($status_list as $s): ?><option value="<?= $s ?>" <?= $status_filter===$s?'selected':''?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-warning px-3">Filter</button>
    <a href="orders.php" class="btn btn-outline-secondary">Reset</a>
  </form>

  <div class="table-card">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Items</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if ($orders->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No orders found</td></tr>
          <?php endif; ?>
          <?php while ($o = $orders->fetch_assoc()): ?>
          <tr>
            <td class="text-warning fw-600"><?= e($o['order_number']) ?></td>
            <td><?= e($o['uname']) ?></td>
            <td class="text-light small"><?= $o['item_count'] ?> item<?= $o['item_count'] > 1 ? 's' : '' ?></td>
            <td class="fw-700"><?= formatPrice($o['total_amount']) ?></td>
            <td class="text-light small"><?= e($o['payment_method']) ?></td>
            <td><span class="status-badge <?= $status_colors[$o['status']] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td class="text-light small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-eye"></i></a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($total_pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" style="background:var(--card-bg);border-color:var(--border);color:var(--text);"
           href="orders.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
