<?php

//  includes/footer.php

$base_f = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
           strpos($_SERVER['PHP_SELF'], '/user/')  !== false) ? '../' : '';
?>
<footer class="footer mt-5">
  <div class="container">
    <div class="row g-4 py-5">
      <div class="col-lg-4">
        <a class="d-flex align-items-center gap-2 text-decoration-none mb-3" href="<?= $base_f ?>index.php">
          <span class="brand-icon"><i class="fas fa-bolt"></i></span>
          <span class="brand-name fs-4">Electro<span class="text-warning">Shop</span></span>
        </a>
        <p class="text small">Your one-stop destination for the latest electronics. Quality products, best prices, fast delivery.</p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Quick Links</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= $base_f ?>index.php">Home</a></li>
          <li><a href="<?= $base_f ?>products.php">Products</a></li>
          <li><a href="<?= $base_f ?>cart.php">Cart</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="<?= $base_f ?>user/orders.php">My Orders</a></li>
          <?php else: ?>
          <li><a href="<?= $base_f ?>login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Categories</h6>
        <ul class="list-unstyled footer-links">
          <?php
          $fc = $conn->query("SELECT name, slug FROM categories LIMIT 6");
          while ($r = $fc->fetch_assoc()):
          ?>
          <li><a href="<?= $base_f ?>products.php?category=<?= e($r['slug']) ?>"><?= e($r['name']) ?></a></li>
          <?php endwhile; ?>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="footer-heading">Contact Us</h6>
        <ul class="list-unstyled footer-contact">
          <li><i class="fas fa-map-marker-alt me-2 text-warning"></i>123 Tech Street, Patan, India</li>
          <li><i class="fas fa-phone me-2 text-warning"></i>+91 98765 43210</li>
          <li><i class="fas fa-envelope me-2 text-warning"></i>support@electroshop.com</li>
        </ul>
        <div class="mt-3 d-flex gap-2">
          <img src="<?= $base_f ?>assets/images/pay-visa.png" height="24" alt="Visa" onerror="this.style.display='none'">
    
        </div>
      </div>
    </div>
    <hr class="border-secondary">
    <div class="row py-3">
      <div class="col-md-6 text small">
        &copy; <?= date('Y') ?> ElectroShop. All rights reserved. | Built with ❤️ for learning.
      </div>
      <div class="col-md-6 text-md-end text small">
        <a href="#" class="text-small me-3">Privacy Policy</a>
        <a href="#" class="text-small me-3">Terms of Service</a>
        <a href="#" class="text-small">Refund Policy</a>
      </div>
    </div>
  </div>
</footer>
