<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$zones = mysqli_query($conn,"
    SELECT t.*,
           COUNT(w.id) AS room_count
    FROM workspace_types t
    LEFT JOIN workspaces w ON w.type_id = t.id
    GROUP BY t.id
    ORDER BY t.floor ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Zones — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-wrapper">

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <h4>Manage Zones</h4>
      <div class="user-chip">
        <span>👤</span>
        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
      </div>
    </div>

    <div class="admin-content">

      <div class="admin-table-wrap">
        <h3>Zone / Workspace Type List</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Zone Name</th>
              <th>Floor</th>
              <th>Capacity</th>
              <th>Rooms</th>
              <th>Booking Unit</th>
              <th>Price</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $count = 0;
            while($z = mysqli_fetch_assoc($zones)):
              $count++;

              // Build price display
              $prices = [];
              if ($z['price_slot'])  $prices[] = 'RM ' . number_format($z['price_slot'],2)  . '/slot';
              if ($z['price_week'])  $prices[] = 'RM ' . number_format($z['price_week'],2)  . '/week';
              if ($z['price_month']) $prices[] = 'RM ' . number_format($z['price_month'],2) . '/month';
              if ($z['price_year'])  $prices[] = 'RM ' . number_format($z['price_year'],2)  . '/year';
              $price_str = $prices ? implode(', ', $prices) : '—';
            ?>
            <tr>
              <td><strong>#<?= $z['id'] ?></strong></td>
              <td>
                <strong><?= htmlspecialchars($z['type_name']) ?></strong>
                <?php if($z['description']): ?>
                <p style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;white-space:normal;">
                  <?= htmlspecialchars(mb_strimwidth($z['description'], 0, 80, '…')) ?>
                </p>
                <?php endif; ?>
              </td>
              <td>Floor <?= (int)$z['floor'] ?></td>
              <td><?= (int)$z['capacity'] ?> pax</td>
              <td>
                <span class="badge badge-available"><?= (int)$z['room_count'] ?> rooms</span>
              </td>
              <td>
                <span style="background:var(--cream-dark);padding:3px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;color:var(--brown-dark);">
                  <?= ucfirst($z['booking_unit']) ?>
                </span>
              </td>
              <td style="font-size:0.83rem;"><?= $price_str ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($count === 0): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text-light);padding:32px;">No zones found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

</body>
</html>
