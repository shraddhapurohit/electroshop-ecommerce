<?php
// ============================================================
//  login.php
// ============================================================
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, profile_image FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($uid, $uname, $ahash, $uimg);
        $stmt->fetch();
        $stmt->close();

        if ($uid && password_verify($password, $ahash)) {
            $_SESSION['user_id']      = $uid;
            $_SESSION['user_name']    = $uname;
            $_SESSION['profile_image'] = $uimg;
            $redirect = $_GET['redirect'] ?? 'index.php';
            redirect($redirect);
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon d-inline-flex" style="width:48px;height:48px;font-size:1.4rem;">
        <i class="fas fa-bolt"></i>
      </span>
    </div>
    <h2 class="auth-title">Welcome Back</h2>
    <p class="auth-subtitle">Sign in to your ElectroShop account</p>

    <?php if ($error): ?>
      <div class="alert-custom alert-error es-flash"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
      <div class="alert-custom alert-success es-flash"><i class="fas fa-check-circle"></i> Account created! Please login.</div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--muted);">
            <i class="fas fa-envelope"></i>
          </span>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--muted);">
            <i class="fas fa-lock"></i>
          </span>
          <input type="password" name="password" id="pwdField" class="form-control" placeholder="Your password" required>
          <button type="button" class="input-group-text cursor-pointer"
                  style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--muted);"
                  onclick="togglePwd()">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary-custom">
        <i class="fas fa-sign-in-alt me-2"></i>Sign In
      </button>
    </form>

    <div class="divider">or</div>
    <p class="text-center text-muted small">
      Don't have an account?
      <a href="register.php" class="text-warning fw-600">Create one</a>
    </p>

    <!-- Demo credentials hint -->
    <div class="mt-3 p-3 rounded" style="background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.15);font-size:0.8rem;color:var(--muted);">
      <i class="fas fa-info-circle text-warning me-1"></i>
      <strong class="text-warning">Demo:</strong> Register a new account to explore all features.
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
function togglePwd() {
    const f = document.getElementById('pwdField');
    const i = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'fas fa-eye-slash'; }
    else { f.type = 'password'; i.className = 'fas fa-eye'; }
}
</script>
</body>
</html>
