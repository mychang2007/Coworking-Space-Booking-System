<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminName = htmlspecialchars($_SESSION['user_name']);

// Toggle room availability
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $wid = (int)$_GET['toggle'];
    mysqli_query($conn,
        "UPDATE workspaces
         SET status = IF(status='available','unavailable','available')
         WHERE id = $wid");
    header("Location: manage_workspace.php"); exit;
}

// Load all rooms
$rows = mysqli_fetch_all(
    mysqli_query($conn, "SELECT w.*, wt.floor, wt.type_name AS room_type
                         FROM workspaces w
                         JOIN workspace_types wt ON w.type_id = wt.id
                         ORDER BY wt.floor, w.room_number"),
    MYSQLI_ASSOC
);
$byFloor = [1 => [], 2 => [], 3 => []];
foreach ($rows as $r) $byFloor[$r['floor']][] = $r;

$floorMeta = [
    1 => ['label' => 'Floor 1 — Single Rooms',    'icon' => '🪑', 'color' => '#8B5E3C'],
    2 => ['label' => 'Floor 2 — Discussion Rooms', 'icon' => '👥', 'color' => '#7C7C45'],
    3 => ['label' => 'Floor 3 — Private Offices',  'icon' => '🏢', 'color' => '#185FA5'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Workspace — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
<style>
.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 14px;
}
.room-tile {
    border-radius: var(--radius-sm);
    padding: 14px 8px;
    text-align: center;
    border: 2px solid;
    transition: transform .15s;
}
.room-tile:hover { transform: translateY(-2px); }
.room-tile.avail   { border-color: #c3e6cb; background: #d4edda; }
.room-tile.unavail { border-color: #f5c6cb; background: #f8d7da; }
.room-tile .rnum   { font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
.room-tile .rbadge { font-size: 0.68rem; font-weight: 700; margin-bottom: 8px;
                     display: block; text-transform: uppercase; }
.avail  .rbadge { color: #155724; }
.unavail .rbadge { color: #721c24; }
.toggle-btn {
    font-size: 0.7rem; padding: 4px 10px;
    border-radius: 999px; border: none; cursor: pointer;
    font-weight: 700; transition: opacity .15s;
}
.toggle-btn:hover { opacity: .85; }
.avail   .toggle-btn { background: #dc3545; color: #fff; }
.unavail .toggle-btn { background: #28a745; color: #fff; }
</style>
</head>
<body>
<div class="admin-wrapper">
  <?php include 'admin_sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h4>Manage Workspace</h4>
      <div class="user-chip"><?= $adminName ?>
        <span class="badge badge-admin" style="margin-left:6px">Admin</span>
      </div>
    </div>

    <div class="admin-content">
      <?php foreach ($byFloor as $flNum => $flRooms):
        $meta  = $floorMeta[$flNum];
        $avail = count(array_filter($flRooms, fn($r) => $r['status'] === 'available'));
        $total = count($flRooms);
      ?>
      <div class="admin-table-wrap" style="margin-bottom:24px">
        <h3 style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <span style="font-size:1.1rem"><?= $meta['icon'] ?></span>
          <?= $meta['label'] ?>
          <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400">
            — <?= $avail ?>/<?= $total ?> available
          </span>
          <span style="margin-left:auto">
            <span class="badge badge-available"><?= $avail ?> Available</span>&nbsp;
            <span class="badge badge-unavailable"><?= $total - $avail ?> Unavailable</span>
          </span>
        </h3>

        <div class="room-grid">
          <?php foreach ($flRooms as $rm):
            $cls = $rm['status'] === 'available' ? 'avail' : 'unavail';
          ?>
          <div class="room-tile <?= $cls ?>">
            <div class="rnum"><?= htmlspecialchars($rm['room_number']) ?></div>
            <span class="rbadge"><?= ucfirst($rm['status']) ?></span>
            <a href="manage_workspace.php?toggle=<?= $rm['id'] ?>"
               onclick="return confirm('Toggle availability for <?= htmlspecialchars($rm['room_number']) ?>?')">
              <button class="toggle-btn">
                <?= $rm['status'] === 'available' ? 'Mark Unavailable' : 'Mark Available' ?>
              </button>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div>
</body>
</html>