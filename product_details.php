<?php

//  product_details.php  –  Single Product Page

require_once 'includes/db.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { redirect('products.php'); }

// Fetch product
$stmt = $conn->prepare("
    SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->bind_param('s', $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { redirect('products.php'); }

// Product images
$imgs_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgs_stmt->bind_param('i', $product['id']);
$imgs_stmt->execute();
$images = $imgs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$imgs_stmt->close();

$primary_img = !empty($images) ? $images[0]['image'] : 'placeholder.png';

// Related products
$rel_stmt = $conn->prepare("
    SELECT p.*,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
    FROM products p
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    LIMIT 4
");
$rel_stmt->bind_param('ii', $product['category_id'], $product['id']);
$rel_stmt->execute();
$related = $rel_stmt->get_result();
$rel_stmt->close();

$discount = 0;
if ($product['original_price'] > 0) {
    $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
}

$page_title = $product['name'] . ' – ElectroShop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
  <!-- Breadcrumb -->
  <div class="es-breadcrumb">
    <a href="index.php">Home</a> &rsaquo;
    <a href="products.php?category=<?= e($product['cat_slug']) ?>"><?= e($product['cat_name']) ?></a> &rsaquo;
    <span class="current"><?= e($product['name']) ?></span>
  </div>

  <div class="row g-5">
    <!-- ── Image Gallery ──────────────────────────────────── -->
    <div class="col-lg-5">
      <div class="product-gallery">
        <div class="main-img-wrap">
          <img id="mainProductImg"
               src="uploads/product_images/<?= e($primary_img) ?>"
               alt="<?= e($product['name']) ?>"
               onerror="this.src='https://via.placeholder.com/400x380/1e293b/64748b?text=No+Image'">
        </div>
        <?php if (count($images) > 1): ?>
        <div class="thumb-list">
          <?php foreach ($images as $idx => $img): ?>
          <div class="thumb-item <?= $idx === 0 ? 'active' : '' ?>"
               onclick="switchImage('uploads/product_images/<?= e($img['image']) ?>', this)">
            <img src="uploads/product_images/<?= e($img['image']) ?>"
                 alt="Thumbnail <?= $idx+1 ?>"
                 onerror="this.src='https://via.placeholder.com/64x64/1e293b/64748b?text=?'">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Product Info ───────────────────────────────────── -->
    <div class="col-lg-7">
      <div class="detail-brand"><?= e($product['brand']) ?> &bull; <?= e($product['cat_name']) ?></div>
      <h1 class="detail-title"><?= e($product['name']) ?></h1>

      <!-- Rating -->
      <div class="d-flex align-items-center gap-2 mb-3">
        <?= starRating($product['rating']) ?>
        <span class="fw-600"><?= $product['rating'] ?></span>
        <span class="text small">(<?= number_format($product['rating_count']) ?> ratings)</span>
        <span class="badge <?= $product['stock'] > 0 ? 'bg-success' : 'bg-danger' ?> ms-2">
          <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
        </span>
      </div>

      <!-- Price -->
      <div class="mb-4">
        <span class="detail-price"><?= formatPrice($product['price']) ?></span>
        <?php if ($product['original_price'] > 0): ?>
          <span class="detail-old-price"><?= formatPrice($product['original_price']) ?></span>
          <?php if ($discount > 0): ?>
            <span class="detail-saving fw-600"><?= $discount ?>% off</span>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($product['original_price'] > $product['price']): ?>
          <div class="text-success small mt-1">
            <i class="fas fa-tag me-1"></i>You save <?= formatPrice($product['original_price'] - $product['price']) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <p class="text mb-4"><?= e($product['description']) ?></p>

      <!-- Action buttons -->
      <?php if ($product['stock'] > 0): ?>
      <div class="d-flex gap-3 mb-4 flex-wrap">
        <button class="btn btn-warning btn-lg fw-bold px-5"
                onclick="addToCart(<?= $product['id'] ?>, this)">
          <i class="fas fa-shopping-cart me-2"></i>Add to Cart
        </button>
        <button class="btn btn-outline-light btn-lg px-4"
                onclick="toggleWishlist(<?= $product['id'] ?>, this)">
          <i class="far fa-heart me-2"></i>Wishlist
        </button>
      </div>
      <a href="cart.php" class="btn btn-success btn-lg px-5 mb-4">
        <i class="fas fa-bolt me-2"></i>Buy Now
      </a>
      <?php else: ?>
        <div class="alert-custom alert-error mb-4">
          <i class="fas fa-times-circle"></i> This product is currently out of stock
        </div>
      <?php endif; ?>

      <!-- Highlights -->
      <div class="row g-3 mb-4">
        <div class="col-6">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="fas fa-shipping-fast text-accent"></i> Free Delivery
          </div>
        </div>
        <div class="col-6">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="fas fa-undo text-accent"></i> 10-day Returns
          </div>
        </div>
        <div class="col-6">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="fas fa-shield-alt text-accent"></i> 1 Year Warranty
          </div>
        </div>
        <div class="col-6">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="fas fa-certificate text-accent"></i> 100% Genuine
          </div>
        </div>
      </div>

      <!-- Specs table (mocked for demo) -->
      <h5 class="mb-3">Specifications</h5>
      <table class="spec-table w-100">
        <tr><td>Brand</td><td><?= e($product['brand']) ?></td></tr>
        <tr><td>Category</td><td><?= e($product['cat_name']) ?></td></tr>
        <tr><td>Stock</td><td><?= $product['stock'] ?> units</td></tr>
        <tr><td>Rating</td><td><?= $product['rating'] ?> / 5 (<?= number_format($product['rating_count']) ?> reviews)</td></tr>
      </table>
    </div>
  </div>

  <!-- ── Related Products ──────────────────────────────────── -->
  <?php if ($related->num_rows > 0): ?>
  <section class="mt-5">
    <h3 class="section-title mb-4">Related Products</h3>
    <div class="row g-3">
      <?php while ($rp = $related->fetch_assoc()): ?>
      <?php $rd = ($rp['original_price'] > 0) ? round((($rp['original_price'] - $rp['price']) / $rp['original_price']) * 100) : 0; ?>
      <div class="col-6 col-md-3">
        <div class="product-card">
          <div class="product-img-wrap">
            <?php if ($rd > 0): ?><span class="badge-discount">-<?= $rd ?>%</span><?php endif; ?>
            <a href="product_details.php?slug=<?= e($rp['slug']) ?>">
              <img src="uploads/product_images/<?= e($rp['img'] ?? 'placeholder.png') ?>"
                   alt="<?= e($rp['name']) ?>"
                   onerror="this.src='https://via.placeholder.com/180x160/1e293b/64748b?text=No+Image'">
            </a>
          </div>
          <div class="product-body">
            <div class="product-brand"><?= e($rp['brand']) ?></div>
            <a href="product_details.php?slug=<?= e($rp['slug']) ?>" class="text-decoration-none">
              <div class="product-name"><?= e($rp['name']) ?></div>
            </a>
            <div class="mb-2">
              <span class="product-price"><?= formatPrice($rp['price']) ?></span>
            </div>
            <button class="btn-add-cart" onclick="addToCart(<?= $rp['id'] ?>, this)">
              <i class="fas fa-shopping-cart me-1"></i> Add to Cart
            </button>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
