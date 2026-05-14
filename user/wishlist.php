<?php

require_once '../includes/db.php';

// ── AJAX toggle ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => '../login.php']);
        exit;
    }

    $uid = (int)$_SESSION['user_id'];
    $pid = (int)($_POST['product_id'] ?? 0);

    // Check if already in wishlist
    $ck = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $ck->bind_param('ii', $uid, $pid);
    $ck->execute();
    $ck->store_result();
    $exists = $ck->num_rows > 0;
    $ck->close();

    if ($exists) {
        $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $del->bind_param('ii', $uid, $pid);
        $del->execute();
        $del->close();
        echo json_encode(['success' => true, 'in_wishlist' => false, 'message' => 'Removed from wishlist']);
    } else {
        $ins = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)");
        $ins->bind_param('ii', $uid, $pid);
        $ins->execute();
        $ins->close();
        echo json_encode(['success' => true, 'in_wishlist' => true, 'message' => 'Added to wishlist!']);
    }
    exit;
}

// ── Page ──────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) redirect('../login.php');
$uid = (int)$_SESSION['user_id'];

// Remove item via GET
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->bind_param('ii', $uid, $rid);
    $del->execute();
    $del->close();
    redirect('wishlist.php');
}

$stmt = $conn->prepare("
    SELECT p.*, w.added_at,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) AS img
    FROM wishlist w JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$wish_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Wishlist – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4">
  <div class="es-breadcrumb"><a href="../index.php">Home</a> &rsaquo; <span class="current">My Wishlist</span></div>
  <h2 class="mb-4">My Wishlist <span class="text-muted fs-5 fw-normal">(<?= count($wish_items) ?> items)</span></h2>

  <?php if (empty($wish_items)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-heart"></i></div>
      <h4>Your wishlist is empty</h4>
      <p class="text-muted">Save products you love to buy them later</p>
      <a href="../products.php" class="btn btn-warning mt-3 px-4">Browse Products</a>
    </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($wish_items as $p): ?>
    <?php $disc = ($p['original_price'] > 0) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0; ?>
    <div class="col-6 col-md-4 col-lg-3" id="wish-row-<?= $p['id'] ?>">
      <div class="product-card">
        <div class="product-img-wrap">
          <?php if ($disc > 0): ?><span class="badge-discount">-<?= $disc ?>%</span><?php endif; ?>
          <a href="../product_details.php?slug=<?= e($p['slug']) ?>">
            <img src="../uploads/product_images/<?= e($p['img'] ?? 'placeholder.png') ?>"
                 alt="<?= e($p['name']) ?>"
                 onerror="this.src='https://via.placeholder.com/200x180/1e293b/64748b?text=No+Image'">
          </a>
          <div class="product-actions" style="opacity:1;">
            <a href="wishlist.php?remove=<?= $p['id'] ?>" class="action-btn wishlisted" title="Remove">
              <i class="fas fa-heart"></i>
            </a>
          </div>
        </div>
        <div class="product-body">
          <div class="product-brand"><?= e($p['brand']) ?></div>
          <a href="../product_details.php?slug=<?= e($p['slug']) ?>" class="text-decoration-none">
            <div class="product-name"><?= e($p['name']) ?></div>
          </a>
          <div class="mb-3">
            <span class="product-price"><?= formatPrice($p['price']) ?></span>
            <?php if ($p['original_price'] > 0): ?>
              <span class="product-old-price"><?= formatPrice($p['original_price']) ?></span>
            <?php endif; ?>
          </div>
          <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>, this)">
            <i class="fas fa-shopping-cart me-1"></i> Move to Cart
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
