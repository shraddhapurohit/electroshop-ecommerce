<?php

// Auth guard – redirect to admin login if not authenticated
if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

if (!function_exists('isActive')) {
  function isActive($page)
  {
    global $current_page;
    return $current_page === $page ? 'active' : '';
  }
}
?>
<!-- ── Sidebar ─────────────────────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand d-flex align-items-center gap-2">
    <span class="brand-icon"><i class="fas fa-bolt"></i></span>
    <span>Electro<span class="text-warning">Admin</span></span>
  </div>

  <div class="p-3 border-bottom" style="border-color:var(--border) !important;">
    <div class="d-flex align-items-center gap-2">
      <div style="width:36px;height:36px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);">
        <?= strtoupper(substr($_SESSION['admin_user'], 0, 1)) ?>
      </div>
      <div>
        <div class="small fw-600"><?= e($_SESSION['admin_user']) ?></div>
        <div class="text-muted" style="font-size:0.72rem;">Administrator</div>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav py-2">
    <div style="padding:8px 20px 4px;font-size:0.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em;">Main</div>
    <a href="dashboard.php" class="<?= isActive('dashboard.php') ?>"><span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a>
    <a href="add_product.php" class="<?= isActive('add_product.php') ?>"><span class="nav-icon"><i class="fas fa-plus-circle"></i></span> Add Product</a>
    <a href="manage_products.php" class="<?= isActive('manage_products.php') ?>"><span class="nav-icon"><i class="fas fa-boxes"></i></span> Products</a>
    <a href="manage_categories.php" class="<?= isActive('manage_categories.php') ?>"><span class="nav-icon"><i class="fas fa-tags"></i></span> Categories</a>

    <div style="padding:8px 20px 4px;font-size:0.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em;margin-top:8px;">Orders & Users</div>
    <a href="orders.php" class="<?= isActive('orders.php') ?>"><span class="nav-icon"><i class="fas fa-shopping-bag"></i></span> Orders</a>
    <a href="users.php" class="<?= isActive('users.php') ?>"><span class="nav-icon"><i class="fas fa-users"></i></span> Users</a>

    <div style="padding:8px 20px 4px;font-size:0.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em;margin-top:8px;">Store</div>
    <a href="../index.php" target="_blank"><span class="nav-icon"><i class="fas fa-store"></i></span> View Store</a>
    <a href="logout.php"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span> Logout</a>
  </nav>
</aside>

<!-- Mobile toggle button -->
<button class="btn btn-warning d-lg-none"
  style="position:fixed;top:12px;left:12px;z-index:200;width:40px;height:40px;padding:0;border-radius:8px;"
  onclick="document.getElementById('adminSidebar').classList.toggle('open')">
  <i class="fas fa-bars"></i>
</button>