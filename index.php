<?php

require_once 'includes/db.php';
$page_title = 'ElectroShop – Best Electronics Online';

// Fetch featured products
$featured = $conn->query("
    SELECT p.*, c.name AS cat_name,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.is_featured = 1 AND p.is_active = 1
    LIMIT 8
");

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch new arrivals
$new_arrivals = $conn->query("
    SELECT p.*,
           (SELECT image FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS img
    FROM products p
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page_title ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <?php include 'includes/navbar.php'; ?>

  <!-- Hero slider -->
  <div id="heroCarousel" class="carousel slide"
    data-bs-ride="carousel"
    data-bs-interval="1500"
    data-bs-pause="false">

    <section class="hero-section">
      <div class="container py-4 py-lg-5">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
          </div>
          <div class="carousel-inner">

            <!-- Slide 1 -->
            <div class="carousel-item active">
              <div class="row align-items-center g-4 py-3">
                <div class="col-lg-6 hero-content">
                  <div class="hero-badge"><i class="fas fa-fire me-1"></i> Hot Deal – Limited Time</div>
                  <h1 class="hero-title">iPhone 17 <span>Pro Max</span><br>Now in India</h1>
                  <p class="text mb-4">A17 Pro chip · Titanium Design · 48MP Camera System</p>
                  <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="hero-price">₹1,34,900</span>
                    <span class="hero-old-price">₹1,49,900</span>
                  </div>
                  <div class="d-flex gap-3 flex-wrap">
                    <a href="product_details.php?slug=iphone-15-pro-max" class="btn btn-warning btn-lg fw-bold px-4">Buy Now</a>
                    <a href="products.php?category=mobiles" class="btn btn-outline-light btn-lg px-4">Explore Mobiles</a>
                  </div>
                </div>
                <div class="col-lg-6 text-center">
                  <div class="hero-img-wrap">
                    <img src="uploads\product_images\iphone17slider.png" alt="iPhone 15 Pro" class="img-fluid" style="max-height:340px;"
                      onerror="this.src='https://via.placeholder.com/320x340/1e293b/f59e0b?text=iPhone+15+Pro'">
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 2 -->
            <div class="carousel-item">
              <div class="row align-items-center g-4 py-3">
                <div class="col-lg-6 hero-content">
                  <div class="hero-badge"><i class="fas fa-laptop me-1"></i> New Launch</div>
                  <h1 class="hero-title">MacBook Air <span>M3</span><br>Supercharged</h1>
                  <p class="text mb-4">M3 Chip · 18-hr Battery · Liquid Retina Display</p>
                  <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="hero-price">₹1,14,900</span>
                    <span class="hero-old-price">₹1,19,900</span>
                  </div>
                  <div class="d-flex gap-3 flex-wrap">
                    <a href="product_details.php?slug=macbook-air-m3" class="btn btn-warning btn-lg fw-bold px-4">Buy Now</a>
                    <a href="products.php?category=laptops" class="btn btn-outline-light btn-lg px-4">Explore Laptops</a>
                  </div>
                </div>
                <div class="col-lg-6 text-center">
                  <div class="hero-img-wrap">
                    <img src="uploads\product_images\macbookslider.png" alt="MacBook Air M3" class="img-fluid" style="max-height:300px;"
                      onerror="this.src='https://via.placeholder.com/380x300/1e293b/3b82f6?text=MacBook+Air+M3'">
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 3 -->
            <div class="carousel-item">
              <div class="row align-items-center g-4 py-3">
                <div class="col-lg-6 hero-content">
                  <div class="hero-badge"><i class="fas fa-headphones me-1"></i> Editor's Choice</div>
                  <h1 class="hero-title">Sony <span>WH-1000XM5</span><br>Noise Cancelling</h1>
                  <p class="text mb-4">Best-in-class ANC · 30hr Battery · Hi-Res Audio</p>
                  <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="hero-price">₹26,990</span>
                    <span class="hero-old-price">₹34,990</span>
                  </div>
                  <div class="d-flex gap-3 flex-wrap">
                    <a href="product_details.php?slug=sony-wh-1000xm5" class="btn btn-warning btn-lg fw-bold px-4">Buy Now</a>
                    <a href="products.php?category=headphones" class="btn btn-outline-light btn-lg px-4">Explore Audio</a>
                  </div>
                </div>
                <div class="col-lg-6 text-center">
                  <div class="hero-img-wrap">
                    <img src="uploads\product_images\sonywh.png" alt="Sony XM5" class="img-fluid" style="max-height:320px;"
                      onerror="this.src='https://via.placeholder.com/320x320/1e293b/c084fc?text=Sony+WH-1000XM5'">
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /.carousel-inner -->
          <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div><!-- /.carousel -->
      </div>
    </section>

  </div>

  <!-- ── Categories Grid ───────────────────────────────────── -->
  <section class="py-5">
    <div class="container">
      <h2 class="section-title mb-4">Shop by Category</h2>
      <div class="row g-3">
        <?php
        $categories->data_seek(0);
        while ($cat = $categories->fetch_assoc()):
        ?>
          <div class="col-6 col-md-3 col-lg-3">
            <a href="products.php?category=<?= e($cat['slug']) ?>" class="text-decoration-none">
              <div class="cat-feature-box">
                <div class="icon"><i class="fas <?= e($cat['icon']) ?>"></i></div>
                <h6><?= e($cat['name']) ?></h6>
              </div>
            </a>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

  <!-- ── Special Offer Banner ──────────────────────────────── -->
  <section class="py-2">
    <div class="container">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="rounded-3 p-4 d-flex justify-content-between align-items-center"
            style="background:linear-gradient(135deg,#1e3a5f,#0f172a);border:1px solid rgba(59,130,246,0.3);">
            <div>
              <div class="badge bg-primary mb-2">Up to 30% OFF</div>
              <h4 class="fw-700 mb-1">Premium Laptops</h4>
              <p class="text small mb-3">Top brands at unbeatable prices</p>
              <a href="products.php?category=laptops" class="btn btn-sm btn-primary px-3">Shop Now</a>
            </div>
            <i class="fas fa-laptop" style="font-size:4rem;color:rgba(59,130,246,0.4);"></i>
          </div>
        </div>
        <div class="col-md-6">
          <div class="rounded-3 p-4 d-flex justify-content-between align-items-center"
            style="background:linear-gradient(135deg,#2d1b00,#0f172a);border:1px solid rgba(245,158,11,0.3);">
            <div>
              <div class="badge mb-2" style="background:var(--accent);color:var(--primary);">New Arrivals</div>
              <h4 class="fw-700 mb-1">Smart Gadgets</h4>
              <p class="text small mb-3">Watches, earbuds & more</p>
              <a href="products.php?category=smart-watches" class="btn btn-sm btn-warning px-3">Explore</a>
            </div>
            <i class="fas fa-clock" style="font-size:4rem;color:rgba(245,158,11,0.3);"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Featured Products ─────────────────────────────────── -->
  <section class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">Featured Products</h2>
        <a href="products.php" class="btn btn-outline-warning btn-sm px-3">View All <i class="fas fa-arrow-right ms-1"></i></a>
      </div>
      <div class="row g-3">
        <?php while ($p = $featured->fetch_assoc()): ?>
          <?php
          $discount = 0;
          if ($p['original_price'] > 0)
            $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
          ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="product-card">
              <div class="product-img-wrap">
                <?php if ($discount > 0): ?>
                  <span class="badge-discount">-<?= $discount ?>%</span>
                <?php endif; ?>
                <a href="product_details.php?slug=<?= e($p['slug']) ?>">
                  <img src="uploads/product_images/<?= e($p['img'] ?? 'placeholder.png') ?>"
                    alt="<?= e($p['name']) ?>"
                    onerror="this.src='https://via.placeholder.com/220x180/1e293b/64748b?text=No+Image'">
                </a>
                <div class="product-actions">
                  <button class="action-btn" onclick="toggleWishlist(<?= $p['id'] ?>, this)" title="Add to Wishlist">
                    <i class="far fa-heart"></i>
                  </button>
                  <a href="product_details.php?slug=<?= e($p['slug']) ?>" class="action-btn" title="Quick View">
                    <i class="fas fa-eye"></i>
                  </a>
                </div>
              </div>
              <div class="product-body">
                <div class="product-brand"><?= e($p['brand']) ?></div>
                <a href="product_details.php?slug=<?= e($p['slug']) ?>" class="text-decoration-none">
                  <div class="product-name"><?= e($p['name']) ?></div>
                </a>
                <div class="product-rating">
                  <?= starRating($p['rating']) ?>
                  <span class="ms-1">(<?= number_format($p['rating_count']) ?>)</span>
                </div>
                <div class="mb-3">
                  <span class="product-price"><?= formatPrice($p['price']) ?></span>
                  <?php if ($p['original_price'] > 0): ?>
                    <span class="product-old-price"><?= formatPrice($p['original_price']) ?></span>
                    <?php if ($discount > 0): ?>
                      <span class="product-saving"><?= $discount ?>% off</span>
                    <?php endif; ?>
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
    </div>
  </section>

  <!-- ── Features Strip ────────────────────────────────────── -->
  <section class="py-4 border-top border-secondary">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <i class="fas fa-shipping-fast fa-2x text-accent mb-2"></i>
          <h6 class="mb-0">Free Delivery</h6>
          <small class="text-muted">Orders over ₹999</small>
        </div>
        <div class="col-6 col-md-3">
          <i class="fas fa-shield-alt fa-2x text-accent mb-2"></i>
          <h6 class="mb-0">1 Year Warranty</h6>
          <small class="text-muted">On all electronics</small>
        </div>
        <div class="col-6 col-md-3">
          <i class="fas fa-undo fa-2x text-accent mb-2"></i>
          <h6 class="mb-0">Easy Returns</h6>
          <small class="text-muted">10-day return policy</small>
        </div>
        <div class="col-6 col-md-3">
          <i class="fas fa-headset fa-2x text-accent mb-2"></i>
          <h6 class="mb-0">24/7 Support</h6>
          <small class="text-muted">Dedicated support team</small>
        </div>
      </div>
    </div>
  </section>

  <!-- ── New Arrivals ──────────────────────────────────────── -->
  <section class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">New Arrivals</h2>
        <a href="products.php" class="btn btn-outline-warning btn-sm px-3">View All</a>
      </div>
      <div class="row g-3">
        <?php while ($p = $new_arrivals->fetch_assoc()): ?>
          <?php $discount2 = ($p['original_price'] > 0) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0; ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="product-card">
              <div class="product-img-wrap">
                <?php if ($discount2 > 0): ?>
                  <span class="badge-discount">-<?= $discount2 ?>%</span>
                <?php endif; ?>
                <a href="product_details.php?slug=<?= e($p['slug']) ?>">
                  <img src="uploads/product_images/<?= e($p['img'] ?? 'placeholder.png') ?>"
                    alt="<?= e($p['name']) ?>"
                    onerror="this.src='https://via.placeholder.com/220x180/1e293b/64748b?text=No+Image'">
                </a>
                <div class="product-actions">
                  <button class="action-btn" onclick="toggleWishlist(<?= $p['id'] ?>, this)"><i class="far fa-heart"></i></button>
                </div>
              </div>
              <div class="product-body">
                <div class="product-brand"><?= e($p['brand']) ?></div>
                <a href="product_details.php?slug=<?= e($p['slug']) ?>" class="text-decoration-none">
                  <div class="product-name"><?= e($p['name']) ?></div>
                </a>
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
    </div>
  </section>

  <?php include 'includes/footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      var myCarousel = document.querySelector('#heroCarousel');
      var carousel = new bootstrap.Carousel(myCarousel, {
        interval: 2500,
        ride: 'carousel',
        pause: false,
        wrap: true
      });
    });
  </script>
  
</body>

</html>