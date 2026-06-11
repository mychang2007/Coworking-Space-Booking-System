<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminName = htmlspecialchars($_SESSION['user_name']);

// ─── 1. HANDLE TOGGLE STATUS ──────────────────────────────
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $wid = (int)$_GET['toggle'];
    mysqli_query($conn,
        "UPDATE workspace
         SET status = IF(status='available','unavailable','available')
         WHERE workspace_id = $wid");
    header("Location: manage_workspace.php"); exit;
}

// ─── 2. HANDLE DELETE ROOM ────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $wid = (int)$_GET['delete'];
    // Note: If you have foreign key constraints (like bookings tied to this room),
    // you may need to delete those first or set them to CASCADE in your database.
    mysqli_query($conn, "DELETE FROM workspace WHERE workspace_id = $wid");
    header("Location: manage_workspace.php"); exit;
}

// ─── 3. HANDLE ADD ROOM ───────────────────────────────────
if (isset($_POST['add_room'])) {
    $name    = mysqli_real_escape_string($conn, $_POST['workspace_name']);
    $zone_id = (int)$_POST['zone_id'];
    mysqli_query($conn, "INSERT INTO workspace (workspace_name, zone_id, status) VALUES ('$name', $zone_id, 'available')");
    header("Location: manage_workspace.php"); exit;
}

// ─── 4. HANDLE EDIT ROOM ──────────────────────────────────
if (isset($_POST['edit_room'])) {
    $wid     = (int)$_POST['workspace_id'];
    $name    = mysqli_real_escape_string($conn, $_POST['workspace_name']);
    $zone_id = (int)$_POST['zone_id'];
    mysqli_query($conn, "UPDATE workspace SET workspace_name = '$name', zone_id = $zone_id WHERE workspace_id = $wid");
    header("Location: manage_workspace.php"); exit;
}

// ─── FETCH ZONES FOR DROPDOWNS ────────────────────────────
$zones = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM zone ORDER BY floor, zone_name"), MYSQLI_ASSOC);

// ─── 5. HANDLE SEARCH ─────────────────────────────────────
$searchQuery = '';
$searchParam = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchParam = trim($_GET['search']);
    $safeSearch  = mysqli_real_escape_string($conn, $searchParam);
    $searchQuery = "WHERE w.workspace_name LIKE '%$safeSearch%'";
}

// ─── LOAD WORKSPACES ──────────────────────────────────────
$rows = mysqli_fetch_all(
    mysqli_query($conn,
        "SELECT w.*, z.zone_name, z.floor
         FROM workspace w
         JOIN zone z ON w.zone_id = z.zone_id
         $searchQuery
         ORDER BY z.floor, w.workspace_name"),
    MYSQLI_ASSOC
);

$byFloor = [1 => [], 2 => [], 3 => []];
foreach ($rows as $r) {
    if (isset($byFloor[$r['floor']])) {
        $byFloor[$r['floor']][] = $r;
    }
}

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
/* Existing Room Tile Styles */
.room-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-top: 14px;
}
.room-tile {
    border-radius: var(--radius-sm); padding: 14px 8px; text-align: center; border: 2px solid; transition: transform .15s; position: relative;
}
.room-tile:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
.room-tile.avail   { border-color: #c3e6cb; background: #d4edda; }
.room-tile.unavail { border-color: #f5c6cb; background: #f8d7da; }
.room-tile .rnum   { font-size: 1.1rem; font-weight: 800; margin-bottom: 2px; }
.room-tile .rbadge { font-size: 0.68rem; font-weight: 700; margin-bottom: 8px; display: block; text-transform: uppercase; }
.avail  .rbadge { color: #155724; }
.unavail .rbadge { color: #721c24; }

.toggle-btn {
    font-size: 0.7rem; padding: 4px 10px; border-radius: 999px; border: none; cursor: pointer; font-weight: 700; transition: opacity .15s; width: 100%; margin-bottom: 8px;
}
.toggle-btn:hover { opacity: .85; }
.avail   .toggle-btn { background: #dc3545; color: #fff; }
.unavail .toggle-btn { background: #28a745; color: #fff; }

/* New: Action Buttons for Edit/Delete */
.action-btns { display: flex; justify-content: center; gap: 8px; }
.icon-btn {
    background: transparent; border: none; cursor: pointer; font-size: 1.1rem; padding: 2px; transition: transform 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;
}
.icon-btn:hover { transform: scale(1.2); }

/* New: Toolbar (Search & Add Button) */
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.search-form { display: flex; gap: 8px; }
.search-form input { padding: 8px 14px; border: 1px solid var(--border); border-radius: var(--radius-sm); outline: none; width: 250px; }
.search-form input:focus { border-color: var(--brown); }

/* New: Modals */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
    display: none; align-items: center; justify-content: center; z-index: 2000;
}
.modal-overlay.active { display: flex; }
.modal-content {
    background: var(--white); width: 100%; max-width: 400px; padding: 32px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-header h3 { margin: 0; font-size: 1.2rem; }
.close-btn { background: transparent; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1; }
.close-btn:hover { color: var(--dark); }
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
      
      <div class="toolbar">
        <form method="GET" action="manage_workspace.php" class="search-form">
            <input type="text" name="search" placeholder="Search room name..." value="<?= htmlspecialchars($searchParam) ?>">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if ($searchParam): ?>
                <a href="manage_workspace.php" class="btn btn-danger btn-sm">Clear</a>
            <?php endif; ?>
        </form>
        <button class="btn btn-primary" onclick="openModal('addModal')">➕ Add New Room</button>
      </div>

      <?php foreach ($byFloor as $flNum => $flRooms):
        $meta  = $floorMeta[$flNum];
        $avail = count(array_filter($flRooms, fn($r) => $r['status'] === 'available'));
        $total = count($flRooms);
        
        // Skip rendering floor if searching and no results found for this floor
        if ($searchParam && $total === 0) continue; 
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
          <?php if ($total === 0): ?>
             <p style="color:var(--text-muted); font-size:0.9rem;">No rooms available.</p>
          <?php endif; ?>

          <?php foreach ($flRooms as $rm):
            $cls = $rm['status'] === 'available' ? 'avail' : 'unavail';
          ?>
          <div class="room-tile <?= $cls ?>">
            <div class="rnum"><?= htmlspecialchars($rm['workspace_name']) ?></div>
            <span class="rbadge"><?= ucfirst($rm['status']) ?></span>
            
            <a href="manage_workspace.php?toggle=<?= $rm['workspace_id'] ?>" style="text-decoration:none;">
              <button class="toggle-btn">
                <?= $rm['status'] === 'available' ? 'Mark Unavailable' : 'Mark Available' ?>
              </button>
            </a>
            
            <div class="action-btns">
              <a href="manage_booking.php?workspace_id=<?= $rm['workspace_id'] ?>" class="icon-btn" title="View Bookings for this Room">
                📅
              </a>
              <button type="button" class="icon-btn" title="Edit Room" 
                      onclick="openEditModal(<?= $rm['workspace_id'] ?>, '<?= htmlspecialchars($rm['workspace_name'], ENT_QUOTES) ?>', <?= $rm['zone_id'] ?>)">
                ✏️
              </button>
              <a href="manage_workspace.php?delete=<?= $rm['workspace_id'] ?>" class="icon-btn" title="Delete Room" 
                 onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($rm['workspace_name']) ?>? This cannot be undone.')">
                🗑️
              </a>
            </div>

          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if ($searchParam && empty($byFloor[1]) && empty($byFloor[2]) && empty($byFloor[3])): ?>
         <div class="admin-table-wrap" style="text-align:center; padding: 40px;">
             <h3>No workspaces found matching "<?= htmlspecialchars($searchParam) ?>"</h3>
         </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<div class="modal-overlay" id="addModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Add New Room</h3>
      <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
    </div>
    <form method="POST" action="manage_workspace.php">
      <div class="form-group">
        <label>Room Name / Number</label>
        <input type="text" name="workspace_name" required placeholder="e.g. S-21 or D-11">
      </div>
      <div class="form-group">
        <label>Assign to Floor / Zone</label>
        <select name="zone_id" required>
          <option value="" disabled selected>Select a Zone</option>
          <?php foreach ($zones as $z): ?>
            <option value="<?= $z['zone_id'] ?>">Floor <?= $z['floor'] ?> — <?= htmlspecialchars($z['zone_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="add_room" class="btn btn-primary" style="width:100%; margin-top:10px;">Save Room</button>
    </form>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Room</h3>
      <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form method="POST" action="manage_workspace.php">
      <input type="hidden" name="workspace_id" id="edit_workspace_id">
      <div class="form-group">
        <label>Room Name / Number</label>
        <input type="text" name="workspace_name" id="edit_workspace_name" required>
      </div>
      <div class="form-group">
        <label>Assign to Floor / Zone</label>
        <select name="zone_id" id="edit_zone_id" required>
          <?php foreach ($zones as $z): ?>
            <option value="<?= $z['zone_id'] ?>">Floor <?= $z['floor'] ?> — <?= htmlspecialchars($z['zone_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="edit_room" class="btn btn-primary" style="width:100%; margin-top:10px;">Update Room</button>
    </form>
  </div>
</div>

<script>
// Open and Close Modal functionality
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Pre-fill Edit form when clicking the edit button
function openEditModal(id, name, zoneId) {
    document.getElementById('edit_workspace_id').value = id;
    document.getElementById('edit_workspace_name').value = name;
    document.getElementById('edit_zone_id').value = zoneId;
    openModal('editModal');
}

// Close modals when clicking outside the box
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}
</script>
</body>
</html>