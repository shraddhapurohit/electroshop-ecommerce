<?php
require_once '../includes/db.php';
require_once 'includes/sidebar.php';

// Delete product
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Delete images from disk
    $imgs = $conn->query("SELECT image FROM product_images WHERE product_id=$del_id");
    while ($img = $imgs->fetch_assoc()) { @unlink('../uploads/product_images/' . $img['image']); }
    $conn->query("DELETE FROM products WHERE id=$del_id");
    redirect('manage_products.php?deleted=1');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $conn->query("UPDATE products SET is_active = 1 - is_active WHERE id=$tid");
    redirect('manage_products.php');
}

// Toggle featured
if (isset($_GET['feature'])) {
    $fid = (int)$_GET['feature'];
    $conn->query("UPDATE products SET is_featured = 1 - is_featured WHERE id=$fid");
    redirect('manage_products.php');
}

// Filters
$search   = trim($_GET['search']   ?? '');
$cat_id   = (int)($_GET['cat']     ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page - 1) * $per_page;

$where = ['1=1'];
$types = ''; $params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.brand LIKE ?)";
    $like = "%$search%"; $params = array_merge($params, [$like, $like]); $types .= 'ss';
}
if ($cat_id) {
    $where[] = "p.category_id = ?"; $params[] = $cat_id; $types .= 'i';
}

$where_str = implode(' AND ', $where);

// Count
$cnt = $conn->prepare("SELECT COUNT(*) FROM products p WHERE $where_str");
if ($params) $cnt->bind_param($types, ...$params);
$cnt->execute(); $cnt->bind_result($total); $cnt->fetch(); $cnt->close();
$total_pages = ceil($total / $per_page);

// Fetch
$sql  = "SELECT p.*, c.name AS cat_name,
                (SELECT image FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS img
         FROM products p JOIN categories c ON c.id=p.category_id
         WHERE $where_str ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($types . 'ii', ...$all_params);
$stmt->execute();
$products = $stmt->get_result();

$all_cats = $conn->query("SELECT id, name FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Products – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-700 mb-0">Manage Products <span class="text-small fs-5 fw-normal">(<?= $total ?>)</span></h3>
    <a href="add_product.php" class="btn btn-warning fw-600 px-3"><i class="fas fa-plus me-1"></i> Add Product</a>
  </div>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert-custom alert-info es-flash"><i class="fas fa-trash"></i> Product deleted successfully.</div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Search products..." value="<?= e($search) ?>">
    <select name="cat" class="form-select" style="max-width:180px;background:var(--card-bg);border-color:var(--border);color:var(--text);">
      <option value="">All Categories</option>
      <?php while ($c = $all_cats->fetch_assoc()): ?>
      <option value="<?= $c['id'] ?>" <?= $cat_id==$c['id']?'selected':''?>><?= e($c['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-warning px-3">Filter</button>
    <a href="manage_products.php" class="btn btn-outline-secondary">Reset</a>
  </form>

  <div class="table-card">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th style="width:60px;">Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Rating</th>
            <th>Featured</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($products->num_rows === 0): ?>
          <tr><td colspan="10" class="text-center py-4 text small">No products found</td></tr>
          <?php endif; ?>
          <?php while ($p = $products->fetch_assoc()): ?>
          <tr>
            <td class="text small"><?= $p['id'] ?></td>
            <td>
              <img src="../uploads/product_images/<?= e($p['img'] ?? 'placeholder.png') ?>"
                   width="44" height="44" style="object-fit:contain;background:rgba(255,255,255,0.04);border-radius:6px;padding:3px;"
                   onerror="this.src='https://via.placeholder.com/44x44/1e293b/64748b?text=?'">
            </td>
            <td>
              <div class="fw-600 small"><?= e(substr($p['name'], 0, 40)) ?><?= strlen($p['name']) > 40 ? '...' : '' ?></div>
              <div class="text" style="font-size:0.72rem;"><?= e($p['brand']) ?></div>
            </td>
            <td class="text small"><?= e($p['cat_name']) ?></td>
            <td>
              <div class="fw-600 small"><?= formatPrice($p['price']) ?></div>
              <?php if ($p['original_price'] > 0): ?>
                <div class="text-decoration-line-through" style="font-size:0.72rem;"><?= formatPrice($p['original_price']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $p['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>"><?= $p['stock'] ?></span>
            </td>
            <td class="small">⭐ <?= $p['rating'] ?></td>
            <td>
              <a href="manage_products.php?feature=<?= $p['id'] ?>" title="Toggle Featured"
                 class="badge <?= $p['is_featured'] ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                <?= $p['is_featured'] ? 'Yes' : 'No' ?>
              </a>
            </td>
            <td>
              <a href="manage_products.php?toggle=<?= $p['id'] ?>"
                 class="status-badge <?= $p['is_active'] ? 'status-active' : 'status-inactive' ?>">
                <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
              </a>
            </td>
            <td>
              <div class="d-flex gap-1">
                <a href="add_product.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="../product_details.php?slug=<?= e($p['slug']) ?>" target="_blank"
                   class="btn btn-sm btn-outline-info" title="View">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="manage_products.php?delete=<?= $p['id'] ?>"
                   onclick="return confirm('Delete this product permanently?')"
                   class="btn btn-sm btn-outline-danger" title="Delete">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" style="background:var(--card-bg);border-color:var(--border);color:var(--text);"
           href="manage_products.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&cat=<?= $cat_id ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
