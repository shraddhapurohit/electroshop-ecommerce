<?php
// ============================================================
//  register.php
// ============================================================
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) redirect('index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Handle profile image upload
            $profile_image = 'default.png';
            if (!empty($_FILES['profile_image']['name'])) {
                $allowed = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.';
                } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                    $error = 'Image must be under 2MB.';
                } else {
                    $filename = 'user_' . uniqid() . '.' . $ext;
                    $dest     = 'uploads/user_images/' . $filename;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                        $profile_image = $filename;
                    }
                }
            }

            if (!$error) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $ins = $conn->prepare("INSERT INTO users (name, email, phone, password, profile_image) VALUES (?,?,?,?,?)");
                $ins->bind_param('sssss', $name, $email, $phone, $hashed, $profile_image);
                if ($ins->execute()) {
                    redirect('login.php?registered=1');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $ins->close();
            }
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="auth-wrapper">
  <div class="auth-card" style="max-width:520px;">
    <div class="auth-logo">
      <span class="brand-icon d-inline-flex" style="width:48px;height:48px;font-size:1.4rem;">
        <i class="fas fa-bolt"></i>
      </span>
    </div>
    <h2 class="auth-title">Create Account</h2>
    <p class="auth-subtitle">Join ElectroShop for the best deals on electronics</p>

    <?php if ($error): ?>
      <div class="alert-custom alert-error es-flash"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>

      <!-- Profile image upload -->
      <div class="text-center mb-4">
        <div class="profile-avatar-wrap d-inline-block">
          <img src="assets/images/default-avatar.png" id="avatarPreview" class="profile-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=User&background=1e293b&color=f59e0b&size=110'">
          <label for="profileImg" class="avatar-edit-btn cursor-pointer">
            <i class="fas fa-camera"></i>
          </label>
        </div>
        <input type="file" name="profile_image" id="profileImg" class="d-none"
               accept="image/*" data-preview="avatarPreview">
        <div class="text-muted small mt-2">Click to upload profile photo</div>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" placeholder="Enter your name"
                 value="<?= e($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" placeholder="xyz@example.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Phone Number</label>
          <input type="tel" name="phone" class="form-control" placeholder="+91 6354285236"
                 value="<?= e($_POST['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Password * <small class="text-muted">(min 6 chars)</small></label>
          <input type="password" name="password" class="form-control" placeholder="Create password" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn-primary-custom">
          <i class="fas fa-user-plus me-2"></i>Create Account
        </button>
      </div>
    </form>

    <div class="divider">or</div>
    <p class="text-center text-muted small">
      Already have an account?
      <a href="login.php" class="text-warning fw-600">Sign In</a>
    </p>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
