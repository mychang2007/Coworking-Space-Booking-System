<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$users      = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM normal_users"))[0];
$workspaces = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM workspaces"))[0];
$bookings   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM bookings"))[0];
$revenue    = mysqli_fetch_row(mysqli_query($conn,"
    SELECT IFNULL(SUM(total_price),0) FROM bookings WHERE status='completed'
"))[0];

$active_count    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM bookings WHERE status='active'"))[0];
$completed_count = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM bookings WHERE status='completed'"))[0];
$cancelled_count = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM bookings WHERE status='cancelled'"))[0];

$recent = mysqli_query($conn,"
    SELECT b.*, u.fullname, w.room_number
    FROM bookings b
    JOIN normal_users u ON b.user_id=u.id
    JOIN workspaces w ON b.workspace_id=w.id
    ORDER BY b.id DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-wrapper">

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <h4>Dashboard Overview</h4>
      <div class="user-chip">
        <span>👤</span>
        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
      </div>
    </div>

    <div class="admin-content">

      <!-- STAT CARDS -->
      <div class="stat-grid">
        <div class="stat-card">
          <span class="stat-label">Total Users</span>
          <span class="stat-value"><?= number_format($users) ?></span>
          <div class="stat-icon stat-icon-brown">👥</div>
        </div>
        <div class="stat-card">
          <span class="stat-label">Workspaces</span>
          <span class="stat-value"><?= number_format($workspaces) ?></span>
          <div class="stat-icon stat-icon-olive">🏢</div>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total Bookings</span>
          <span class="stat-value"><?= number_format($bookings) ?></span>
          <div class="stat-icon stat-icon-blue">📋</div>
        </div>
        <div class="stat-card">
          <span class="stat-label">Revenue</span>
          <span class="stat-value">RM <?= number_format($revenue, 2) ?></span>
          <div class="stat-icon stat-icon-brown">💰</div>
        </div>
      </div>

      <!-- BOOKING STATUS ROW -->
      <div class="stat-grid" style="margin-bottom:32px">
        <div class="stat-card">
          <span class="stat-label">Active</span>
          <span class="stat-value" style="color:#155724"><?= $active_count ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Completed</span>
          <span class="stat-value" style="color:#0c5460"><?= $completed_count ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Cancelled</span>
          <span class="stat-value" style="color:#721c24"><?= $cancelled_count ?></span>
        </div>
      </div>

      <!-- RECENT BOOKINGS TABLE -->
      <div class="admin-table-wrap">
        <h3>Recent Bookings</h3>
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
            <?php while($r = mysqli_fetch_assoc($recent)): ?>
            <tr>
              <td><strong>#<?= $r['id'] ?></strong></td>
              <td><?= htmlspecialchars($r['fullname']) ?></td>
              <td><?= htmlspecialchars($r['room_number']) ?></td>
              <td>
                <?php
                  $sc = ['active'=>'badge-active','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
                  $cls = $sc[$r['status']] ?? 'badge-user';
                ?>
                <span class="badge <?= $cls ?>"><?= ucfirst($r['status']) ?></span>
              </td>
              <td><a href="manageBooking.php" class="btn btn-sm btn-outline">View All</a></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /admin-content -->
  </main>
</div>

</body>
</html>
