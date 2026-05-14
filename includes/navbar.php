<?php
// ============================================================
//  includes/navbar.php
// ============================================================
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
  $cart_count = getCartCount($conn, $_SESSION['user_id']);
}

// Determine base path for links
$base = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
  strpos($_SERVER['PHP_SELF'], '/user/')  !== false) ? '../' : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNavbar">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $base ?>index.php">
      <span class="brand-icon"><i class="fas fa-bolt"></i></span>
      <span class="brand-name">Electro<span class="text-warning">Shop</span></span>
    </a>

    <!-- Search bar (desktop) -->
    <form class="d-none d-lg-flex search-form mx-3 flex-grow-1" action="<?= $base ?>products.php" method="GET">
      <div class="input-group">
        <input type="text" class="form-control search-input" name="search"
          placeholder="Search for mobiles, laptops, TVs..."
          value="<?= e($_GET['search'] ?? '') ?>">
        <button class="btn btn-warning" type="submit"><i class="fas fa-search"></i></button>
      </div>
    </form>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <!-- Search bar (mobile) -->
      <form class="d-lg-none my-2" action="<?= $base ?>products.php" method="GET">
        <div class="input-group">
          <input type="text" class="form-control" name="search"
            placeholder="Search products..." value="<?= e($_GET['search'] ?? '') ?>">
          <button class="btn btn-warning" type="submit"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <!-- category -->
      <div class="dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" style="color: white;">
          Categories
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow">
          <?php
          $cats = $conn->query("SELECT * FROM categories ORDER BY name");
          while ($cat = $cats->fetch_assoc()):
          ?>
            <li>
              <a class="dropdown-item" href="<?= $base ?>products.php?category=<?= e($cat['slug']) ?>">
                <i class="fas <?= e($cat['icon']) ?> me-1"></i><?= e($cat['name']) ?>
              </a>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>


      <!-- orders -->
      <li class="nav-item">
        <a class="nav-link" href="<?= $base ?>user/orders.php">
          <i class="fas fa-truck me-1"></i>Track Order
        </a>
      </li>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <!-- Cart -->
        <li class="nav-item">
          <a class="nav-link nav-icon-link" href="<?= $base ?>cart.php">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
          </a>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Wishlist -->
          <li class="nav-item">
            <a class="nav-link nav-icon-link" href="<?= $base ?>user/wishlist.php">
              <i class="fas fa-heart"></i>
            </a>
          </li>
          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
              <img src="<?= $base ?>uploads/user_images/<?= e($_SESSION['profile_image'] ?? 'default.png') ?>"
                class="rounded-circle" width="30" height="30" style="object-fit:cover;" alt="Profile">
              <span class="d-none d-lg-inline">Hi, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
              <li><a class="dropdown-item" href="<?= $base ?>user/profile.php"><i class="fas fa-user me-2 text-primary"></i>My Profile</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item text-danger" href="<?= $base ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-warning btn-sm px-3 ms-1" href="<?= $base ?>register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Category bar -->
</nav>