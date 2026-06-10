<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id     = intval($_POST['booking_id']);
    $action = $_POST['action'];

    if ($action === 'complete') {
        mysqli_query($conn, "UPDATE bookings SET status='completed' WHERE id=$id");
        $message = 'Booking #' . $id . ' marked as completed.';
    }
    if ($action === 'cancel') {
        mysqli_query($conn, "UPDATE bookings SET status='cancelled' WHERE id=$id");
        $message = 'Booking #' . $id . ' has been cancelled.';
    }
}

$filter = $_GET['status'] ?? 'all';
$where  = ($filter !== 'all') ? "WHERE b.status='" . mysqli_real_escape_string($conn, $filter) . "'" : '';

$bookings = mysqli_query($conn,"
    SELECT b.*, u.fullname, w.room_number
    FROM bookings b
    JOIN normal_users u ON b.user_id=u.id
    JOIN workspaces w ON b.workspace_id=w.id
    $where
    ORDER BY b.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Bookings — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-wrapper">

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <h4>Manage Bookings</h4>
      <div class="user-chip">
        <span>👤</span>
        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
      </div>
    </div>

    <div class="admin-content">

      <?php if($message): ?>
      <div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:var(--radius-sm);padding:12px 18px;margin-bottom:20px;font-size:0.88rem;font-weight:600;">
        ✓ <?= htmlspecialchars($message) ?>
      </div>
      <?php endif; ?>

      <!-- FILTER TABS -->
      <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
        <?php foreach(['all','active','completed','cancelled'] as $f): ?>
          <a href="?status=<?= $f ?>"
             style="padding:7px 18px;border-radius:999px;font-size:0.82rem;font-weight:600;text-decoration:none;
                    <?= $filter===$f
                      ? 'background:var(--brown);color:#fff;'
                      : 'background:var(--white);color:var(--text-muted);border:1px solid var(--border);' ?>">
            <?= ucfirst($f) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="admin-table-wrap">
        <h3>Booking List<?= $filter!=='all' ? ' — ' . ucfirst($filter) : '' ?></h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Room</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $count = 0;
            while($b = mysqli_fetch_assoc($bookings)):
              $count++;
            ?>
            <tr>
              <td><strong>#<?= $b['id'] ?></strong></td>
              <td><?= htmlspecialchars($b['fullname']) ?></td>
              <td><?= htmlspecialchars($b['room_number']) ?></td>
              <td>
                <?php
                  $sc = ['active'=>'badge-active','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
                  $cls = $sc[$b['status']] ?? 'badge-user';
                ?>
                <span class="badge <?= $cls ?>"><?= ucfirst($b['status']) ?></span>
              </td>
              <td>
                <?php if($b['status'] === 'active'): ?>
                <div style="display:flex;gap:8px;">
                  <form method="POST" style="margin:0">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button name="action" value="complete" class="btn btn-sm btn-olive">Complete</button>
                  </form>
                  <form method="POST" style="margin:0">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button name="action" value="cancel" class="btn btn-sm btn-danger">Cancel</button>
                  </form>
                </div>
                <?php else: ?>
                <span style="color:var(--text-light);font-size:0.82rem;">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if($count === 0): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-light);padding:32px;">No bookings found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

</body>
</html>
