<?php
// ============================================================
//  user/profile.php
// ============================================================
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) redirect('../login.php?redirect=user/profile.php');
$uid = (int)$_SESSION['user_id'];

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city']    ?? '');
    $state   = trim($_POST['state']   ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if (empty($name)) { $error = 'Name is required.'; }
    else {
        $new_img = $user['profile_image'];

        // Handle image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image format.';
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $error = 'Image must be under 2MB.';
            } else {
                $filename = 'user_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], '../uploads/user_images/' . $filename)) {
                    // Delete old image
                    if ($user['profile_image'] !== 'default.png') {
                        @unlink('../uploads/user_images/' . $user['profile_image']);
                    }
                    $new_img = $filename;
                }
            }
        }

        if (!$error) {
            $upd = $conn->prepare("UPDATE users SET name=?,phone=?,address=?,city=?,state=?,pincode=?,profile_image=? WHERE id=?");
            $upd->bind_param('sssssssi', $name, $phone, $address, $city, $state, $pincode, $new_img, $uid);
            $upd->execute();
            $upd->close();

            // Update session
            $_SESSION['user_name']     = $name;
            $_SESSION['profile_image'] = $new_img;
            $success = 'Profile updated successfully!';

            // Re-fetch
            $stmt2 = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt2->bind_param('i', $uid);
            $stmt2->execute();
            $user = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile – ElectroShop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container py-4" style="max-width:720px;">
  <div class="es-breadcrumb"><a href="../index.php">Home</a> &rsaquo; <span class="current">My Profile</span></div>
  <h2 class="mb-4">My Profile</h2>

  <?php if ($success): ?>
    <div class="alert-custom alert-success es-flash"><i class="fas fa-check-circle"></i> <?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert-custom alert-error es-flash"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="checkout-card">
    <form method="POST" enctype="multipart/form-data">
      <!-- Avatar -->
      <div class="text-center mb-4">
        <div class="profile-avatar-wrap d-inline-block">
          <img src="../uploads/user_images/<?= e($user['profile_image']) ?>" id="avatarPreview"
               class="profile-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=1e293b&color=f59e0b&size=110'">
          <label for="profileImg" class="avatar-edit-btn cursor-pointer"><i class="fas fa-camera"></i></label>
        </div>
        <input type="file" name="profile_image" id="profileImg" class="d-none" accept="image/*" data-preview="avatarPreview">
        <div class="text-muted small mt-2">Click photo to change</div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email <small class="text-muted">(cannot change)</small></label>
          <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control" value="<?= e($user['city'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="2"><?= e($user['address'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">State</label>
          <select name="state" class="form-select">
            <option value="">Select State</option>
            <?php foreach (['Andhra Pradesh','Delhi','Gujarat','Karnataka','Kerala','Maharashtra','Rajasthan','Tamil Nadu','Telangana','Uttar Pradesh','West Bengal'] as $st): ?>
            <option value="<?= $st ?>" <?= ($user['state'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Pincode</label>
          <input type="text" name="pincode" class="form-control" maxlength="6" value="<?= e($user['pincode'] ?? '') ?>">
        </div>
      </div>

      <div class="mt-4 d-flex gap-3">
        <button type="submit" class="btn btn-warning fw-bold px-4">
          <i class="fas fa-save me-2"></i>Save Changes
        </button>
        <a href="../user/orders.php" class="btn btn-outline-light px-4">
          <i class="fas fa-box me-2"></i>My Orders
        </a>
      </div>
    </form>
  </div>

  <!-- Account info card -->
  <div class="checkout-card mt-4">
    <h6 class="fw-700 mb-3"><i class="fas fa-info-circle me-2 text-accent"></i>Account Info</h6>
    <div class="row g-2 text-muted small">
      <div class="col-6">Member since: <span class="text-white"><?= date('M Y', strtotime($user['created_at'])) ?></span></div>
      <div class="col-6">Account ID: <span class="text-white">#<?= str_pad($user['id'], 5, '0', STR_PAD_LEFT) ?></span></div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
