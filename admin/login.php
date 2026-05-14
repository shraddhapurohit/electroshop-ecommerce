<?php
// ============================================================
//  admin/login.php
// ============================================================
require_once '../includes/db.php';

if (isset($_SESSION['admin_id'])) {
  header('Location: dashboard.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($username && $password) {
    $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $aid = $row['id'] ?? null;
    $ahash = $row['password'] ?? null;
    $stmt->close();

    if ($aid) {
      $_SESSION['admin_id']   = $aid;
      $_SESSION['admin_user'] = $username;
      header('Location: dashboard.php');
      exit;
    } else {
      $error = 'Invalid username or password.';
    }
  } else {
    $error = 'Please fill in all fields.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <div class="auth-wrapper">
    <div class="auth-card" style="max-width:420px;">
      <div class="auth-logo">
        <span class="brand-icon d-inline-flex" style="width:52px;height:52px;font-size:1.5rem;"><i class="fas fa-bolt"></i></span>
      </div>
      <h2 class="auth-title">Admin Panel</h2>
      <p class="auth-subtitle">Sign in to manage ElectroShop</p>

      <?php if ($error): ?>
        <div class="alert-custom alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--muted);">
              <i class="fas fa-user"></i>
            </span>
            <input type="text" name="username" class="form-control" placeholder="admin" required>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--muted);">
              <i class="fas fa-lock"></i>
            </span>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
        </div>
        <button type="submit" class="btn-primary-custom"><i class="fas fa-sign-in-alt me-2"></i>Sign In to Admin</button>
      </form>

      <div class="mt-4 p-3 rounded text-center" style="background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.15);font-size:0.8rem;color:var(--muted);">
        Default credentials: <strong class="text-warning">admin</strong> / <strong class="text-warning">admin123</strong>
      </div>
      <div class="text-center mt-3"><a href="../index.php" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Back to Store</a></div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>