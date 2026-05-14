<?php
// ============================================================
//  products.php  –  Product Listing with Filters
// ============================================================
require_once 'includes/db.php';

// ── Get filters from GET ─────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$cat_slug = trim($_GET['category'] ?? '');
$sort     = trim($_GET['sort']     ?? 'newest');
$max_price= (int)($_GET['max_price'] ?? 200000);
$brand    = trim($_GET['brand']    ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

// ── Build query ──────────────────────────────────────────────
$where = ["p.is_active = 1", "p.price <= $max_price"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]   = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $like      = "%$search%";
    $params    = array_merge($params, [$like, $like, $like]);
    $types    .= 'sss';
}

$cat_id = 0;
if ($cat_slug !== '') {
    $cs = $conn->prepare("SELECT id, name FROM categories WHERE slug = ?");
    $cs->bind_param('s', $cat_slug);
    $cs->execute();
    $cs->bind_result($cat_id, $cat_name_filter);
    $cs->fetch();
    $cs->close();
    if ($cat_id) { $where[] = "p.category_id = ?"; $params[] = $cat_id; $types .= 'i'; }
}

if ($brand !== '') {
    $where[] = "p.brand = ?";
    $params[] = $brand;
    $types .= 's';
}

$where_str = implode(' AND ', $where);

$order = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating'     => 'p.rating DESC',
    'popular'    => 'p.rating_count DESC',
    default      => 'p.created_at DESC'
};

// Total count
$cnt_sql  = "SELECT COUNT(*) FROM products p WHERE $where_str";
$cnt_stmt = $conn->prepare($cnt_sql);
if ($params) { $cnt_stmt->bind_param($types, ...$params); }
$cnt_stmt->execute();
$cnt_stmt->bind_result($total_products);
$cnt_stmt->fetch();
$cnt_stmt->close();
$total_pages = ceil($total_products / $per_page);

// Products
$sql  = "SELECT p.*, c.name AS cat_name,
                (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE $where_str
         ORDER BY $order
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params = array_merge($params, [$per_page, $offset]);
$all_types  = $types . 'ii';
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$products = $stmt->get_result();

// Brands for sidebar
$brands_q = $conn->query("SELECT DISTINCT brand FROM products WHERE is_active = 1 AND brand != '' ORDER BY brand");

$page_title = $search ? "Search: $search" : ($cat_slug ? ($cat_name_filter ?? ucfirst($cat_slug)) : 'All Products');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
  <!-- Breadcrumb -->
  <div class="es-breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <span class="current"><?= e($page_title) ?></span>
  </div>

  <div class="row g-4">
    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <div class="col-lg-3 d-none d-lg-block">
      <form method="GET" id="filterForm">
        <?php if ($search): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
        <div class="filter-sidebar">
          <!-- Categories -->
          <div class="mb-4">
            <div class="filter-title">Categories</div>
            <?php
            $all_cats = $conn->query("SELECT * FROM categories ORDER BY name");
            while ($c = $all_cats->fetch_assoc()):
            ?>
            <div class="filter-item <?= ($cat_slug === $c['slug']) ? 'active' : '' ?>"
                 onclick="window.location.href='products.php?category=<?= e($c['slug']) ?><?= $search ? '&search='.urlencode($search) : '' ?>'">
              <i class="fas <?= e($c['icon']) ?> me-2"></i><?= e($c['name']) ?>
            </div>
            <?php endwhile; ?>
            <div class="filter-item <?= ($cat_slug === '') ? 'active' : '' ?>"
                 onclick="window.location.href='products.php<?= $search ? '?search='.urlencode($search) : '' ?>'">
              <i class="fas fa-th me-2"></i>All Categories
            </div>
          </div>

          <!-- Price Range -->
          <div class="mb-4">
            <div class="filter-title">Max Price: <span id="priceDisplay">₹<?= number_format($max_price) ?></span></div>
            <input type="range" class="price-range" id="priceRange" name="max_price"
                   min="1000" max="200000" step="1000" value="<?= $max_price ?>">
          </div>

          <!-- Brands -->
          <div class="mb-4">
            <div class="filter-title">Brand</div>
            <select name="brand" class="form-select form-select-sm mb-2" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);">
              <option value="">All Brands</option>
              <?php while ($b = $brands_q->fetch_assoc()): ?>
              <option value="<?= e($b['brand']) ?>" <?= ($brand === $b['brand']) ? 'selected' : '' ?>><?= e($b['brand']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Sort -->
          <div class="mb-4">
            <div class="filter-title">Sort By</div>
            <select name="sort" class="form-select form-select-sm" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);">
              <option value="newest"     <?= $sort==='newest'     ?'selected':''?>>Newest First</option>
              <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':''?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort==='price_desc' ?'selected':''?>>Price: High to Low</option>
              <option value="rating"     <?= $sort==='rating'     ?'selected':''?>>Top Rated</option>
              <option value="popular"    <?= $sort==='popular'    ?'selected':''?>>Most Popular</option>
            </select>
          </div>

          <button type="submit" class="btn-primary-custom">Apply Filters</button>
          <a href="products.php" class="btn btn-sm btn-outline-secondary w-100 mt-2">Reset All</a>
        </div>
      </form>
    </div>

    <!-- ── Products ─────────────────────────────────────────── -->
    <div class="col-lg-9">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0"><?= e($page_title) ?> <span class="text-muted fs-6 fw-normal">(<?= $total_products ?> products)</span></h5>
        <!-- Mobile sort -->
        <select class="form-select form-select-sm d-lg-none" style="width:auto;background:var(--card-bg);border-color:var(--border);color:var(--text);"
                onchange="window.location.href=this.value">
          <?php
          $base_url = "products.php?" . http_build_query(array_filter(['search'=>$search,'category'=>$cat_slug,'brand'=>$brand,'max_price'=>$max_price]));
          foreach (['newest'=>'Newest','price_asc'=>'Price ↑','price_desc'=>'Price ↓','rating'=>'Top Rated'] as $val=>$lbl):
          ?>
          <option value="<?= $base_url ?>&sort=<?= $val ?>" <?= $sort===$val?'selected':''?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Products grid -->
      <?php if ($total_products === 0): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-search"></i></div>
          <h4>No products found</h4>
          <p class="text-muted">Try adjusting your filters or search terms</p>
          <a href="products.php" class="btn btn-warning mt-2">View All Products</a>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php while ($p = $products->fetch_assoc()): ?>
        <?php $disc = ($p['original_price'] > 0) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0; ?>
        <div class="col-6 col-md-4">
          <div class="product-card">
            <div class="product-img-wrap">
              <?php if ($disc > 0): ?><span class="badge-discount">-<?= $disc ?>%</span><?php endif; ?>
              <a href="product_details.php?slug=<?= e($p['slug']) ?>">
                <img src="uploads/product_images/<?= e($p['img'] ?? 'placeholder.png') ?>"
                     alt="<?= e($p['name']) ?>"
                     onerror="this.src='https://via.placeholder.com/200x180/1e293b/64748b?text=No+Image'">
              </a>
              <div class="product-actions">
                <button class="action-btn" onclick="toggleWishlist(<?= $p['id'] ?>, this)"><i class="far fa-heart"></i></button>
                <a href="product_details.php?slug=<?= e($p['slug']) ?>" class="action-btn"><i class="fas fa-eye"></i></a>
              </div>
            </div>
            <div class="product-body">
              <div class="product-brand"><?= e($p['brand']) ?></div>
              <a href="product_details.php?slug=<?= e($p['slug']) ?>" class="text-decoration-none">
                <div class="product-name"><?= e($p['name']) ?></div>
              </a>
              <div class="product-rating"><?= starRating($p['rating']) ?> <small>(<?= $p['rating_count'] ?>)</small></div>
              <div class="mb-3">
                <span class="product-price"><?= formatPrice($p['price']) ?></span>
                <?php if ($p['original_price'] > 0): ?>
                  <span class="product-old-price"><?= formatPrice($p['original_price']) ?></span>
                <?php endif; ?>
              </div>
              <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>, this)">
                <i class="fas fa-shopping-cart me-1"></i> Add to Cart
              </button>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          <?php
          $q_params = array_filter(['search'=>$search,'category'=>$cat_slug,'sort'=>$sort,'brand'=>$brand,'max_price'=>$max_price]);
          for ($i = 1; $i <= $total_pages; $i++):
          ?>
          <li class="page-item <?= $i===$page ? 'active' : '' ?>">
            <a class="page-link" style="background:var(--card-bg);border-color:var(--border);color:var(--text);"
               href="products.php?<?= http_build_query(array_merge($q_params, ['page'=>$i])) ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
