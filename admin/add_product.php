<?php
// ============================================================
//  admin/add_product.php  –  Add or Edit Product
// ============================================================
require_once '../includes/db.php';
require_once 'includes/sidebar.php';

$edit_id = (int)($_GET['edit'] ?? 0);
$product = null;
$images  = [];

if ($edit_id) {
    $es = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $es->bind_param('i', $edit_id);
    $es->execute();
    $product = $es->get_result()->fetch_assoc();
    $es->close();

    $is = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
    $is->bind_param('i', $edit_id);
    $is->execute();
    $images = $is->get_result()->fetch_all(MYSQLI_ASSOC);
    $is->close();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name']          ?? '');
    $category_id   = (int)($_POST['category_id']  ?? 0);
    $description   = trim($_POST['description']   ?? '');
    $price         = (float)($_POST['price']      ?? 0);
    $original_price= (float)($_POST['original_price'] ?? 0);
    $stock         = (int)($_POST['stock']         ?? 0);
    $brand         = trim($_POST['brand']          ?? '');
    $rating        = (float)($_POST['rating']      ?? 0);
    $rating_count  = (int)($_POST['rating_count']  ?? 0);
    $is_featured   = isset($_POST['is_featured'])  ? 1 : 0;
    $is_active     = isset($_POST['is_active'])    ? 1 : 0;

    if (!$name || !$category_id || $price <= 0) {
        $error = 'Name, category and price are required.';
    } else {
        $slug = createSlug($name);
        // Make slug unique
        if ($edit_id) {
            $sc = $conn->prepare("SELECT id FROM products WHERE slug=? AND id != ?");
            $sc->bind_param('si', $slug, $edit_id);
        } else {
            $sc = $conn->prepare("SELECT id FROM products WHERE slug=?");
            $sc->bind_param('s', $slug);
        }
        $sc->execute(); $sc->store_result();
        if ($sc->num_rows > 0) $slug .= '-' . time();
        $sc->close();

        if ($edit_id) {
            $upd = $conn->prepare("UPDATE products SET category_id=?,name=?,slug=?,description=?,price=?,original_price=?,stock=?,brand=?,rating=?,rating_count=?,is_featured=?,is_active=? WHERE id=?");
            $upd->bind_param('isssddisdiiii', $category_id,$name,$slug,$description,$price,$original_price,$stock,$brand,$rating,$rating_count,$is_featured,$is_active,$edit_id);
            $upd->execute(); $upd->close();
            $product_id = $edit_id;
            $success = 'Product updated successfully!';
        } else {
            $ins = $conn->prepare("INSERT INTO products (category_id,name,slug,description,price,original_price,stock,brand,rating,rating_count,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $ins->bind_param('isssddisdiii', $category_id,$name,$slug,$description,$price,$original_price,$stock,$brand,$rating,$rating_count,$is_featured,$is_active);
            $ins->execute();
            $product_id = $conn->insert_id;
            $ins->close();
            $success = 'Product added successfully!';
        }

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $first   = true;

            // If editing and adding new images, don't auto-set primary if already exists
            if ($edit_id) {
                $pc = $conn->query("SELECT COUNT(*) FROM product_images WHERE product_id=$product_id")->fetch_row()[0];
                if ($pc > 0) $first = false;
            }

            foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
                if (empty($_FILES['images']['name'][$idx])) continue;
                $ext = strtolower(pathinfo($_FILES['images']['name'][$idx], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed) || $_FILES['images']['size'][$idx] > 5*1024*1024) continue;
                $fname = 'prod_' . $product_id . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($tmp, '../uploads/product_images/' . $fname)) {
                    $is_primary = ($first && $idx === 0) ? 1 : 0;
                    $pis = $conn->prepare("INSERT INTO product_images (product_id, image, is_primary) VALUES (?,?,?)");
                    $pis->bind_param('isi', $product_id, $fname, $is_primary);
                    $pis->execute(); $pis->close();
                }
            }
        }

        // Set primary image
        if (!empty($_POST['set_primary'])) {
            $pid_img = (int)$_POST['set_primary'];
            $conn->query("UPDATE product_images SET is_primary=0 WHERE product_id=$product_id");
            $conn->query("UPDATE product_images SET is_primary=1 WHERE id=$pid_img");
        }

        // Delete an image
        if (!empty($_POST['delete_image'])) {
            $del_img_id = (int)$_POST['delete_image'];
            $dq = $conn->query("SELECT image FROM product_images WHERE id=$del_img_id");
            $drow = $dq->fetch_assoc();
            if ($drow) { @unlink('../uploads/product_images/' . $drow['image']); }
            $conn->query("DELETE FROM product_images WHERE id=$del_img_id");
        }

        // Re-fetch for edit mode
        if ($edit_id) {
            $es2 = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $es2->bind_param('i', $edit_id); $es2->execute();
            $product = $es2->get_result()->fetch_assoc(); $es2->close();
            $is2 = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
            $is2->bind_param('i', $edit_id); $is2->execute();
            $images = $is2->get_result()->fetch_all(MYSQLI_ASSOC); $is2->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $edit_id ? 'Edit' : 'Add' ?> Product – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-700 mb-0"><?= $edit_id ? 'Edit Product' : 'Add New Product' ?></h3>
    <a href="manage_products.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
  </div>

  <?php if ($success): ?><div class="alert-custom alert-success es-flash"><i class="fas fa-check-circle"></i> <?= e($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert-custom alert-error   es-flash"><i class="fas fa-times-circle"></i> <?= e($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
      <!-- Left -->
      <div class="col-lg-8">
        <div class="checkout-card">
          <h6 class="fw-700 mb-3">Product Information</h6>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Product Name *</label>
              <input type="text" name="name" class="form-control" value="<?= e($product['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category *</label>
              <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php
                $categories->data_seek(0);
                while ($cat = $categories->fetch_assoc()):
                ?>
                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Brand</label>
              <input type="text" name="brand" class="form-control" value="<?= e($product['brand'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="4"><?= e($product['description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <h6 class="fw-700 mb-3">Pricing & Stock</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Selling Price (₹) *</label>
              <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $product['price'] ?? '' ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">MRP / Original Price (₹)</label>
              <input type="number" name="original_price" class="form-control" step="0.01" min="0" value="<?= $product['original_price'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Stock Quantity</label>
              <input type="number" name="stock" class="form-control" min="0" value="<?= $product['stock'] ?? 0 ?>">
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <h6 class="fw-700 mb-3">Product Images <small class="text-muted">(JPG/PNG/WEBP, max 5MB each)</small></h6>

          <?php if (!empty($images)): ?>
          <div class="d-flex gap-2 flex-wrap mb-3">
            <?php foreach ($images as $img): ?>
            <div class="position-relative" style="width:90px;">
              <img src="../uploads/product_images/<?= e($img['image']) ?>"
                   width="90" height="90" style="object-fit:contain;background:rgba(255,255,255,0.05);border-radius:8px;border:2px solid <?= $img['is_primary'] ? 'var(--accent)' : 'var(--border)' ?>;padding:4px;"
                   onerror="this.src='https://via.placeholder.com/90x90/1e293b/64748b?text=?'">
              <?php if ($img['is_primary']): ?>
                <span style="position:absolute;top:2px;left:2px;background:var(--accent);color:var(--primary);font-size:0.6rem;padding:1px 5px;border-radius:4px;font-weight:700;">PRIMARY</span>
              <?php else: ?>
                <button type="submit" name="set_primary" value="<?= $img['id'] ?>" class="btn btn-xs"
                        style="position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,0.7);color:#fff;font-size:0.6rem;padding:1px 5px;border-radius:4px;border:none;cursor:pointer;">
                  Set Primary
                </button>
              <?php endif; ?>
              <button type="submit" name="delete_image" value="<?= $img['id'] ?>"
                      onclick="return confirm('Delete this image?')"
                      style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border:none;width:20px;height:20px;border-radius:50%;font-size:0.65rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div style="border:2px dashed var(--border);border-radius:10px;padding:24px;text-align:center;cursor:pointer;"
               onclick="document.getElementById('imgUpload').click()">
            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
            <div class="text-muted small">Click to upload images (multiple allowed)</div>
            <div id="imgNames" class="text-muted mt-1" style="font-size:0.75rem;"></div>
          </div>
          <input type="file" name="images[]" id="imgUpload" multiple accept="image/*" class="d-none"
                 onchange="document.getElementById('imgNames').textContent = Array.from(this.files).map(f=>f.name).join(', ')">
        </div>
      </div>

      <!-- Right -->
      <div class="col-lg-4">
        <div class="checkout-card">
          <h6 class="fw-700 mb-3">Settings</h6>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" name="is_active" id="isActive" class="form-check-input" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
              <label for="isActive" class="form-check-label">Active (visible in store)</label>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input type="checkbox" name="is_featured" id="isFeatured" class="form-check-input" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
              <label for="isFeatured" class="form-check-label">Featured on Homepage</label>
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <h6 class="fw-700 mb-3">Rating (Mock)</h6>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">Rating (0-5)</label>
              <input type="number" name="rating" class="form-control" step="0.1" min="0" max="5" value="<?= $product['rating'] ?? 0 ?>">
            </div>
            <div class="col-6">
              <label class="form-label small">Review Count</label>
              <input type="number" name="rating_count" class="form-control" min="0" value="<?= $product['rating_count'] ?? 0 ?>">
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <button type="submit" class="btn-primary-custom py-3">
            <i class="fas fa-<?= $edit_id ? 'save' : 'plus-circle' ?> me-2"></i>
            <?= $edit_id ? 'Update Product' : 'Add Product' ?>
          </button>
          <?php if ($edit_id): ?>
          <a href="manage_products.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </form>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
