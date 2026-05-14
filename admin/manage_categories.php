<?php
// ============================================================
//  admin/manage_categories.php
// ============================================================
require_once '../includes/db.php';
require_once 'includes/sidebar.php';

$success = $error = '';
$edit_cat = null;

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $cnt = $conn->query("SELECT COUNT(*) FROM products WHERE category_id=$did")->fetch_row()[0];
    if ($cnt > 0) {
        $error = "Cannot delete: $cnt products exist in this category.";
    } else {
        $conn->query("DELETE FROM categories WHERE id=$did");
        $success = 'Category deleted.';
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_cat = $conn->query("SELECT * FROM categories WHERE id=$eid")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-microchip');
    $eid  = (int)($_POST['edit_id'] ?? 0);

    if (!$name) {
        $error = 'Category name is required.';
    } else {
        $slug = createSlug($name);
        if ($eid) {
            $sc = $conn->prepare("SELECT id FROM categories WHERE slug=? AND id != ?");
            $sc->bind_param('si', $slug, $eid);
        } else {
            $sc = $conn->prepare("SELECT id FROM categories WHERE slug=?");
            $sc->bind_param('s', $slug);
        }
        $sc->execute(); $sc->store_result();
        if ($sc->num_rows > 0) $slug .= '-' . time();
        $sc->close();

        if ($eid) {
            $upd = $conn->prepare("UPDATE categories SET name=?,slug=?,icon=? WHERE id=?");
            $upd->bind_param('sssi', $name, $slug, $icon, $eid);
            $upd->execute(); $upd->close();
            $success = 'Category updated!';
        } else {
            $ins = $conn->prepare("INSERT INTO categories (name, slug, icon) VALUES (?,?,?)");
            $ins->bind_param('sss', $name, $slug, $icon);
            $ins->execute(); $ins->close();
            $success = 'Category added!';
        }
        $edit_cat = null;
    }
}

$categories = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id ORDER BY c.name
");

$fa_icons = ['fa-mobile-alt','fa-laptop','fa-tablet-alt','fa-headphones','fa-camera','fa-tv','fa-clock','fa-plug','fa-gamepad','fa-keyboard','fa-mouse','fa-print','fa-wifi','fa-battery-full','fa-microchip'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Categories – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="admin-main">
  <h3 class="fw-700 mb-4">Manage Categories</h3>

  <?php if ($success): ?><div class="alert-custom alert-success es-flash"><i class="fas fa-check-circle"></i> <?= e($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert-custom alert-error   es-flash"><i class="fas fa-times-circle"></i> <?= e($error) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Add / Edit form -->
    <div class="col-lg-4">
      <div class="checkout-card">
        <h6 class="fw-700 mb-3"><?= $edit_cat ? 'Edit Category' : 'Add New Category' ?></h6>
        <form method="POST">
          <?php if ($edit_cat): ?>
            <input type="hidden" name="edit_id" value="<?= $edit_cat['id'] ?>">
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" value="<?= e($edit_cat['name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Font Awesome Icon</label>
            <select name="icon" class="form-select" id="iconSelect" onchange="updatePreview(this.value)">
              <?php foreach ($fa_icons as $ico): ?>
              <option value="<?= $ico ?>" <?= ($edit_cat['icon'] ?? 'fa-microchip') === $ico ? 'selected' : '' ?>>
                <?= $ico ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="mt-2 text-muted small">Preview: <i id="iconPreview" class="fas <?= e($edit_cat['icon'] ?? 'fa-microchip') ?>"></i></div>
          </div>
          <button type="submit" class="btn-primary-custom">
            <i class="fas fa-<?= $edit_cat ? 'save' : 'plus' ?> me-1"></i> <?= $edit_cat ? 'Update' : 'Add Category' ?>
          </button>
          <?php if ($edit_cat): ?>
            <a href="manage_categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="col-lg-8">
      <div class="table-card">
        <table class="admin-table">
          <thead>
            <tr><th>Icon</th><th>Name</th><th>Slug</th><th>Products</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while ($c = $categories->fetch_assoc()): ?>
            <tr>
              <td><i class="fas <?= e($c['icon']) ?> text-accent fa-lg"></i></td>
              <td class="fw-600"><?= e($c['name']) ?></td>
              <td class="text-muted small"><?= e($c['slug']) ?></td>
              <td><span class="badge bg-secondary"><?= $c['product_count'] ?></span></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="manage_categories.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                  <a href="manage_categories.php?delete=<?= $c['id'] ?>"
                     onclick="return confirm('Delete this category?')"
                     class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function updatePreview(val) {
    document.getElementById('iconPreview').className = 'fas ' + val;
}
</script>
</body>
</html>
