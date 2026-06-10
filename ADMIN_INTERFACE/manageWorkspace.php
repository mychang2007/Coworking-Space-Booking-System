<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$workspaces = mysqli_query($conn,"
    SELECT w.*, t.type_name
    FROM workspaces w
    JOIN workspace_types t ON w.type_id=t.id
    ORDER BY w.id ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Workspaces — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-wrapper">

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <h4>Manage Workspaces</h4>
      <div class="user-chip">
        <span>👤</span>
        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
      </div>
    </div>

    <div class="admin-content">

      <div class="admin-table-wrap">
        <h3>Workspace List</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Room</th>
              <th>Type</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $count = 0;
            while($w = mysqli_fetch_assoc($workspaces)):
              $count++;
            ?>
            <tr>
              <td><strong>#<?= $w['id'] ?></strong></td>
              <td><?= htmlspecialchars($w['room_number']) ?></td>
              <td>
                <span style="background:var(--cream-dark);padding:3px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;color:var(--brown-dark);">
                  <?= htmlspecialchars($w['type_name']) ?>
                </span>
              </td>
              <td>
                <?php $cls = $w['status']==='available' ? 'badge-available' : 'badge-unavailable'; ?>
                <span class="badge <?= $cls ?>"><?= ucfirst($w['status']) ?></span>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if($count === 0): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--text-light);padding:32px;">No workspaces found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

</body>
</html>
