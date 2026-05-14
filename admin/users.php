<?php
// ============================================================
//  admin/users.php
// ============================================================
require_once '../includes/db.php';
require_once 'includes/sidebar.php';

$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page - 1) * $per_page;

$where  = ['1=1']; $params = []; $types = '';
if ($search) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= 'sss';
}
$where_str = implode(' AND ', $where);

$cnt = $conn->prepare("SELECT COUNT(*) FROM users u WHERE $where_str");
if ($params) $cnt->bind_param($types, ...$params);
$cnt->execute(); $cnt->bind_result($total); $cnt->fetch(); $cnt->close();
$total_pages = ceil($total / $per_page);

$sql  = "SELECT u.*, COUNT(DISTINCT o.id) AS order_count, COALESCE(SUM(o.total_amount),0) AS total_spent FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE $where_str GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$ap   = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($types . 'ii', ...$ap);
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-700 mb-0">Users <span class="text-muted fs-5 fw-normal">(<?= $total ?>)</span></h3>
  </div>

  <form method="GET" class="d-flex gap-2 mb-3">
    <input type="text" name="search" class="form-control" style="max-width:280px;" placeholder="Search by name, email, phone..." value="<?= e($search) ?>">
    <button type="submit" class="btn btn-warning px-3">Search</button>
    <a href="users.php" class="btn btn-outline-secondary">Reset</a>
  </form>

  <div class="table-card">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr><th>#</th><th>Avatar</th><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php if ($users->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No users found</td></tr>
          <?php endif; ?>
          <?php while ($u = $users->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= str_pad($u['id'], 4, '0', STR_PAD_LEFT) ?></td>
            <td>
              <img src="../uploads/user_images/<?= e($u['profile_image']) ?>"
                   width="36" height="36" style="border-radius:50%;object-fit:cover;"
                   onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>&size=36&background=1e293b&color=f59e0b'">
            </td>
            <td class="fw-600"><?= e($u['name']) ?></td>
            <td class="text-muted small"><?= e($u['email']) ?></td>
            <td class="text-muted small"><?= e($u['phone'] ?: '—') ?></td>
            <td><span class="badge bg-secondary"><?= $u['order_count'] ?></span></td>
            <td class="fw-600 text-accent"><?= formatPrice($u['total_spent']) ?></td>
            <td class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($total_pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($i=1; $i<=$total_pages; $i++): ?>
      <li class="page-item <?= $i===$page?'active':''?>">
        <a class="page-link" style="background:var(--card-bg);border-color:var(--border);color:var(--text);"
           href="users.php?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
