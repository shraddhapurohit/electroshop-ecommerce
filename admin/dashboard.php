<?php
// ============================================================
//  admin/dashboard.php
// ============================================================
require_once '../includes/db.php';
require_once 'includes/sidebar.php';   // also handles auth guard

// ── Stats ────────────────────────────────────────────────────
$total_users    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_products = $conn->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];

$pending_orders   = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetch_row()[0];
$out_of_stock     = $conn->query("SELECT COUNT(*) FROM products WHERE stock=0 AND is_active=1")->fetch_row()[0];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.name AS user_name
    FROM orders o JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC LIMIT 8
");

// Top products (most ordered)
$top_products = $conn->query("
    SELECT p.name, p.brand, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue,
           (SELECT image FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS img
    FROM order_items oi JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id ORDER BY sold DESC LIMIT 5
");

// Revenue last 7 days
$revenue_days = $conn->query("
    SELECT DATE(created_at) AS day, SUM(total_amount) AS total
    FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled'
    GROUP BY DATE(created_at) ORDER BY day
");
$days_data = [];
while ($r = $revenue_days->fetch_assoc()) $days_data[$r['day']] = $r['total'];

$status_colors = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard – ElectroShop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; // already included above for auth, but PHP won't double-include ?>

<main class="admin-main">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-700 mb-0">Dashboard</h3>
      <div class="text-muted small"><?= date('l, d F Y') ?></div>
    </div>
    <a href="add_product.php" class="btn btn-warning fw-600 px-3">
      <i class="fas fa-plus me-1"></i> Add Product
    </a>
  </div>

  <!-- ── Stat Cards ──────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-rupee-sign"></i></div>
        <div class="stat-value"><?= formatPrice($total_revenue) ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">Total Orders <span class="text-warning small">(<?= $pending_orders ?> pending)</span></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-boxes"></i></div>
        <div class="stat-value"><?= $total_products ?></div>
        <div class="stat-label">Products <span class="text-danger small">(<?= $out_of_stock ?> OOS)</span></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card red">
        <div class="stat-icon red"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Registered Users</div>
      </div>
    </div>
  </div>

  <!-- ── Quick Actions ──────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <?php
    $qa = [
      ['add_product.php','fa-plus-circle','Add Product','blue'],
      ['manage_categories.php','fa-tags','Manage Categories','green'],
      ['orders.php','fa-shopping-bag','View Orders','orange'],
      ['users.php','fa-users','View Users','red'],
    ];
    foreach ($qa as [$link, $icon, $label, $color]):
    ?>
    <div class="col-6 col-md-3">
      <a href="<?= $link ?>" class="text-decoration-none">
        <div class="stat-card <?= $color ?> text-center py-3">
          <div class="stat-icon <?= $color ?> mx-auto mb-2"><i class="fas <?= $icon ?>"></i></div>
          <div class="small fw-600"><?= $label ?></div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
      <div class="table-card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="border-color:var(--border)!important;">
          <h6 class="fw-700 mb-0">Recent Orders</h6>
          <a href="orders.php" class="btn btn-sm btn-outline-warning">View All</a>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
              <?php while ($o = $recent_orders->fetch_assoc()): ?>
              <tr>
                <td><a href="orders.php?view=<?= $o['id'] ?>" class="text-warning"><?= e($o['order_number']) ?></a></td>
                <td><?= e($o['user_name']) ?></td>
                <td class="fw-600"><?= formatPrice($o['total_amount']) ?></td>
                <td><span class="status-badge <?= $status_colors[$o['status']] ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="text-muted small"><?= date('d M', strtotime($o['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Top Products -->
    <div class="col-lg-4">
      <div class="table-card">
        <div class="p-3 border-bottom" style="border-color:var(--border)!important;">
          <h6 class="fw-700 mb-0">Top Selling Products</h6>
        </div>
        <div class="p-3">
          <?php while ($tp = $top_products->fetch_assoc()): ?>
          <div class="d-flex align-items-center gap-3 mb-3">
            <img src="../uploads/product_images/<?= e($tp['img'] ?? 'placeholder.png') ?>"
                 width="42" height="42" style="object-fit:contain;background:rgba(255,255,255,0.04);border-radius:8px;padding:4px;"
                 onerror="this.src='https://via.placeholder.com/42x42/1e293b/64748b?text=?'">
            <div class="flex-grow-1">
              <div class="small fw-600 text-truncate" style="max-width:150px;"><?= e($tp['name']) ?></div>
              <div class="text-muted" style="font-size:0.72rem;"><?= $tp['sold'] ?> sold</div>
            </div>
            <div class="text-warning small fw-600"><?= formatPrice($tp['revenue']) ?></div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
