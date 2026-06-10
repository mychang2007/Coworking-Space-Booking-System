<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$msg = ''; $err = '';

// ── ADD ZONE ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type_name   = trim($_POST['type_name'] ?? '');
    $floor       = (int)($_POST['floor'] ?? 1);
    $prefix      = strtoupper(trim($_POST['room_code_prefix'] ?? ''));
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $booking_unit = in_array($_POST['booking_unit'] ?? '', ['slot','week','month','year']) ? $_POST['booking_unit'] : 'slot';
    $price_slot  = is_numeric($_POST['price_slot'] ?? '') ? (float)$_POST['price_slot'] : null;
    $price_week  = is_numeric($_POST['price_week'] ?? '') ? (float)$_POST['price_week'] : null;
    $price_month = is_numeric($_POST['price_month'] ?? '') ? (float)$_POST['price_month'] : null;
    $price_year  = is_numeric($_POST['price_year'] ?? '') ? (float)$_POST['price_year'] : null;
    $description = trim($_POST['description'] ?? '');

    if (!$type_name || !$prefix || strlen($prefix) !== 2) {
        $err = 'Zone Name and a 2-character Room Code Prefix are required.';
    } else {
        $st = mysqli_prepare($conn,
            "INSERT INTO workspace_types (type_name, floor, room_code_prefix, capacity, booking_unit, price_slot, price_week, price_month, price_year, description)
             VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'siisisddds',
            $type_name, $floor, $prefix, $capacity, $booking_unit,
            $price_slot, $price_week, $price_month, $price_year, $description);
        mysqli_stmt_execute($st) ? $msg = "Zone \"$type_name\" added successfully." : $err = mysqli_error($conn);
    }
}

// ── UPDATE ZONE ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)$_POST['zone_id'];
    $type_name   = trim($_POST['type_name'] ?? '');
    $floor       = (int)($_POST['floor'] ?? 1);
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $booking_unit = in_array($_POST['booking_unit'] ?? '', ['slot','week','month','year']) ? $_POST['booking_unit'] : 'slot';
    $price_slot  = is_numeric($_POST['price_slot'] ?? '') ? (float)$_POST['price_slot'] : null;
    $price_week  = is_numeric($_POST['price_week'] ?? '') ? (float)$_POST['price_week'] : null;
    $price_month = is_numeric($_POST['price_month'] ?? '') ? (float)$_POST['price_month'] : null;
    $price_year  = is_numeric($_POST['price_year'] ?? '') ? (float)$_POST['price_year'] : null;
    $description = trim($_POST['description'] ?? '');

    $st = mysqli_prepare($conn,
        "UPDATE workspace_types SET type_name=?, floor=?, capacity=?, booking_unit=?, price_slot=?, price_week=?, price_month=?, price_year=?, description=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'siisddddsi',
        $type_name, $floor, $capacity, $booking_unit,
        $price_slot, $price_week, $price_month, $price_year, $description, $id);
    mysqli_stmt_execute($st) ? $msg = "Zone updated successfully." : $err = mysqli_error($conn);
}

// ── DELETE ZONE ───────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if any workspaces exist under this zone
    $chk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM workspaces WHERE type_id=$id"));
    if ($chk['c'] > 0) {
        $err = 'Cannot delete zone: it still has ' . $chk['c'] . ' workspace(s) assigned to it.';
    } else {
        mysqli_query($conn, "DELETE FROM workspace_types WHERE id=$id");
        $msg = 'Zone deleted.';
    }
}

// ── SEARCH & LIST ─────────────────────────────
$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE t.type_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                       OR t.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'" : '';

$zones = mysqli_query($conn,"
    SELECT t.*,
           COUNT(w.id) AS room_count
    FROM workspace_types t
    LEFT JOIN workspaces w ON w.type_id = t.id
    $where
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
<style>
.modal-bg {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:999;
    align-items:center; justify-content:center;
}
.modal-bg.open { display:flex; }
.modal-box {
    background:#fff; border-radius:var(--radius-lg);
    padding:32px; width:100%; max-width:500px;
    box-shadow:var(--shadow-lg); margin:20px;
    max-height:92vh; overflow-y:auto;
}
.price-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
</style>
</head>
<body>

<div class="admin-wrapper">

  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <h4>Manage Zones</h4>
      <div style="display:flex;gap:10px;align-items:center">
        <button class="btn btn-primary btn-sm" onclick="openAdd()">+ Add Zone</button>
        <div class="user-chip">
          <span>👤</span>
          <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        </div>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <!-- Search Bar -->
      <form style="display:flex;gap:8px;margin-bottom:20px;max-width:420px">
        <input type="text" name="search" placeholder="Search zone name or description…"
               value="<?= htmlspecialchars($search) ?>"
               style="flex:1;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:0.88rem;outline:none">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?>
          <a href="manage_zone.php" class="btn btn-outline btn-sm">✕ Clear</a>
        <?php endif; ?>
      </form>

      <div class="admin-table-wrap">
        <h3>Zone / Facility List</h3>
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
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $count = 0;
            while($z = mysqli_fetch_assoc($zones)):
              $count++;
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
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <button class="btn btn-sm btn-outline"
                    onclick='openEdit(<?= htmlspecialchars(json_encode($z)) ?>)'>Edit</button>
                  <a href="?delete=<?= $z['id'] ?>&search=<?= urlencode($search) ?>"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Delete zone &quot;<?= htmlspecialchars($z['type_name']) ?>&quot;? This will fail if rooms are still assigned.')">
                     Delete
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if($count === 0): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--text-light);padding:32px;">No zones found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<!-- ── Add Zone Modal ──────────────────────── -->
<div class="modal-bg" id="addModal">
  <div class="modal-box">
    <h3 style="margin-bottom:20px">➕ Add New Zone</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Zone Name *</label>
        <input type="text" name="type_name" placeholder="e.g. Conference Room" required>
      </div>
      <div class="price-grid">
        <div class="form-group">
          <label>Floor *</label>
          <input type="number" name="floor" value="1" min="1" required>
        </div>
        <div class="form-group">
          <label>Room Code Prefix * (2 chars)</label>
          <input type="text" name="room_code_prefix" maxlength="2" placeholder="e.g. CR" required style="text-transform:uppercase">
        </div>
      </div>
      <div class="price-grid">
        <div class="form-group">
          <label>Capacity (pax)</label>
          <input type="number" name="capacity" value="1" min="1">
        </div>
        <div class="form-group">
          <label>Booking Unit</label>
          <select name="booking_unit">
            <option value="slot">Slot (4hr)</option>
            <option value="week">Weekly</option>
            <option value="month">Monthly</option>
            <option value="year">Yearly</option>
          </select>
        </div>
      </div>
      <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:8px">Prices (leave blank if not applicable)</p>
      <div class="price-grid">
        <div class="form-group"><label>Price/Slot (RM)</label><input type="number" name="price_slot" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Week (RM)</label><input type="number" name="price_week" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Month (RM)</label><input type="number" name="price_month" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Year (RM)</label><input type="number" name="price_year" step="0.01" min="0" placeholder="0.00"></div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Brief description of this zone/facility…"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Add Zone</button>
        <button type="button" class="btn btn-outline" onclick="closeAdd()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit Zone Modal ─────────────────────── -->
<div class="modal-bg" id="editModal">
  <div class="modal-box">
    <h3 style="margin-bottom:20px">✏️ Edit Zone</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="zone_id" id="eID">
      <div class="form-group">
        <label>Zone Name *</label>
        <input type="text" name="type_name" id="eName" required>
      </div>
      <div class="price-grid">
        <div class="form-group">
          <label>Floor *</label>
          <input type="number" name="floor" id="eFloor" min="1" required>
        </div>
        <div class="form-group">
          <label>Capacity (pax)</label>
          <input type="number" name="capacity" id="eCapacity" min="1">
        </div>
      </div>
      <div class="form-group">
        <label>Booking Unit</label>
        <select name="booking_unit" id="eUnit">
          <option value="slot">Slot (4hr)</option>
          <option value="week">Weekly</option>
          <option value="month">Monthly</option>
          <option value="year">Yearly</option>
        </select>
      </div>
      <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:8px">Prices (leave blank if not applicable)</p>
      <div class="price-grid">
        <div class="form-group"><label>Price/Slot (RM)</label><input type="number" name="price_slot" id="ePSlot" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Week (RM)</label><input type="number" name="price_week" id="ePWeek" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Month (RM)</label><input type="number" name="price_month" id="ePMonth" step="0.01" min="0" placeholder="0.00"></div>
        <div class="form-group"><label>Price/Year (RM)</label><input type="number" name="price_year" id="ePYear" step="0.01" min="0" placeholder="0.00"></div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="eDesc" rows="3"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeEdit()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAdd()  { document.getElementById('addModal').classList.add('open'); }
function closeAdd() { document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click', e => { if(e.target===document.getElementById('addModal')) closeAdd(); });

function openEdit(z) {
  document.getElementById('eID').value       = z.id;
  document.getElementById('eName').value     = z.type_name;
  document.getElementById('eFloor').value    = z.floor;
  document.getElementById('eCapacity').value = z.capacity;
  document.getElementById('eUnit').value     = z.booking_unit;
  document.getElementById('ePSlot').value    = z.price_slot  || '';
  document.getElementById('ePWeek').value    = z.price_week  || '';
  document.getElementById('ePMonth').value   = z.price_month || '';
  document.getElementById('ePYear').value    = z.price_year  || '';
  document.getElementById('eDesc').value     = z.description || '';
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if(e.target===document.getElementById('editModal')) closeEdit(); });
</script>
</body>
</html>

