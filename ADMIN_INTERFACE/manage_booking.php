<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminName = htmlspecialchars($_SESSION['user_name']);
$msg = ''; $err = '';

// ── CANCEL ────────────────────────────────────
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    mysqli_query($conn,
        "UPDATE bookings SET status='cancelled' WHERE id=".(int)$_GET['cancel']);
    header("Location: manage_booking.php"); exit;
}

// ── DELETE ────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    mysqli_query($conn,
        "DELETE FROM bookings WHERE id=".(int)$_GET['delete']);
    header("Location: manage_booking.php"); exit;
}

// ── UPDATE BOOKING (POST) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_id'])) {
    $bid    = (int)$_POST['update_booking_id'];
    $status = in_array($_POST['status'] ?? '', ['active','completed','cancelled'])
              ? $_POST['status'] : 'active';
    $notes  = trim($_POST['notes'] ?? '');
    $st     = mysqli_prepare($conn,
                "UPDATE bookings SET status=?, notes=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssi', $status, $notes, $bid);
    mysqli_stmt_execute($st) ? $msg = 'Booking updated.' : $err = 'Update failed.';
}

// ── ADD BOOKING (POST) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $user_id      = (int)$_POST['user_id'];
    $workspace_id = (int)$_POST['workspace_id'];
    $booking_type = in_array($_POST['booking_type'] ?? '', ['slot','week','month','year'])
                    ? $_POST['booking_type'] : 'slot';
    $start_dt     = trim($_POST['start_datetime'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    // Look up price from workspace_types
    $ws_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT wt.price_slot, wt.price_week, wt.price_month, wt.price_year, wt.booking_unit
         FROM workspaces w JOIN workspace_types wt ON w.type_id=wt.id
         WHERE w.id=$workspace_id"));

    $price_map = [
        'slot'  => $ws_row['price_slot'],
        'week'  => $ws_row['price_week'],
        'month' => $ws_row['price_month'],
        'year'  => $ws_row['price_year'],
    ];
    $total_price = (float)($price_map[$booking_type] ?? 0);

    // Calculate end_datetime
    $start_ts = strtotime($start_dt);
    $end_ts = match($booking_type) {
        'slot'  => strtotime('+4 hours', $start_ts),
        'week'  => strtotime('+1 week', $start_ts),
        'month' => strtotime('+1 month', $start_ts),
        'year'  => strtotime('+1 year', $start_ts),
        default => strtotime('+4 hours', $start_ts),
    };
    $end_dt = date('Y-m-d H:i:s', $end_ts);

    $booking_code = 'BK-' . strtoupper(substr(md5(uniqid()), 0, 8));

    if (!$user_id || !$workspace_id || !$start_dt) {
        $err = 'Please fill in all required fields.';
    } else {
        $st = mysqli_prepare($conn,
            "INSERT INTO bookings (booking_code, user_id, workspace_id, booking_type, start_datetime, end_datetime, total_price, notes)
             VALUES (?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'siisssds',
            $booking_code, $user_id, $workspace_id, $booking_type, $start_dt, $end_dt, $total_price, $notes);
        mysqli_stmt_execute($st) ? $msg = "Booking $booking_code added successfully." : $err = mysqli_error($conn);
    }
}

// ── FILTERS ───────────────────────────────────
$filter = in_array($_GET['filter'] ?? '', ['all','active','completed','cancelled'])
          ? ($_GET['filter'] ?? 'all') : 'all';
$search = trim($_GET['search'] ?? '');

$where = "WHERE 1=1";
if ($filter !== 'all')
    $where .= " AND b.status='" . mysqli_real_escape_string($conn, $filter) . "'";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (b.booking_code LIKE '%$s%'
                  OR u.fullname LIKE '%$s%'
                  OR u.email LIKE '%$s%')";
}

$bookings = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.*, u.fullname, u.email, u.phone,
            w.room_number, wt.type_name AS room_type, wt.floor
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN workspaces w ON b.workspace_id = w.id
     JOIN workspace_types wt ON w.type_id = wt.id
     $where
     ORDER BY b.created_at DESC"), MYSQLI_ASSOC);

// Load customers & workspaces for Add modal dropdowns
$customers   = mysqli_fetch_all(mysqli_query($conn, "SELECT id, fullname, email FROM normal_users WHERE status='active' ORDER BY fullname"), MYSQLI_ASSOC);
$workspaces  = mysqli_fetch_all(mysqli_query($conn,
    "SELECT w.id, w.room_number, wt.type_name, wt.floor, wt.price_slot, wt.price_week, wt.price_month, wt.price_year
     FROM workspaces w JOIN workspace_types wt ON w.type_id=wt.id
     WHERE w.status='available' ORDER BY wt.floor, w.room_number"), MYSQLI_ASSOC);

$typeLabel = ['single'=>'Single','discussion'=>'Discussion','office'=>'Office'];
$durLabel  = ['slot'=>'4-hr Slot','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Booking — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
<!-- QR Code generator -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- QR Scanner -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<style>
.modal-bg {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:999;
    align-items:center; justify-content:center;
}
.modal-bg.open { display:flex; }
.modal-box {
    background:#fff; border-radius:var(--radius-lg);
    padding:32px; width:100%; max-width:480px;
    box-shadow:var(--shadow-lg); margin:20px;
    max-height:92vh; overflow-y:auto;
}
#reader { border-radius:var(--radius-sm); overflow:hidden; width:100%; }
.filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
</style>
</head>
<body>
<div class="admin-wrapper">
  <?php include 'admin_sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h4>Manage Booking</h4>
      <div style="display:flex;gap:10px;align-items:center">
        <button class="btn btn-primary btn-sm" onclick="openAdd()">+ Add Booking</button>
        <button class="btn btn-olive btn-sm" onclick="openScanner()">📷 Scan QR</button>
        <div class="user-chip"><?= $adminName ?>
          <span class="badge badge-admin" style="margin-left:6px">Admin</span>
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

      <!-- Filter Bar -->
      <div class="filter-bar">
        <form style="display:flex;gap:8px;flex:1;min-width:220px">
          <input type="text" name="search"
                 placeholder="Search booking ID, name, email…"
                 value="<?= htmlspecialchars($search) ?>"
                 style="flex:1;padding:9px 14px;border:1.5px solid var(--border);
                        border-radius:var(--radius-sm);font-size:0.88rem;outline:none">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
          <?php if ($search): ?>
            <a href="manage_booking.php?filter=<?= $filter ?>" class="btn btn-outline btn-sm">✕ Clear</a>
          <?php endif; ?>
        </form>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <?php foreach (['all','active','completed','cancelled'] as $f): ?>
          <a href="?filter=<?= $f ?>&search=<?= urlencode($search) ?>"
             class="btn btn-sm <?= $filter===$f ? 'btn-primary' : 'btn-outline' ?>">
             <?= ucfirst($f) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Table -->
      <div class="admin-table-wrap">
        <h3 style="margin-bottom:16px">
          Bookings
          <span style="font-weight:400;font-size:0.85rem;color:var(--text-muted)">
            (<?= count($bookings) ?> result<?= count($bookings)!==1?'s':'' ?>)
          </span>
        </h3>
        <?php if (empty($bookings)): ?>
          <p style="text-align:center;padding:30px;color:var(--text-muted)">No bookings found.</p>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Room</th>
              <th>Duration</th>
              <th>Start Date</th>
              <th>Price</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td>
                <span style="font-family:monospace;font-size:0.78rem;color:var(--brown);
                             cursor:pointer;text-decoration:underline"
                      onclick="showQR('<?= htmlspecialchars($b['booking_code']) ?>')">
                  <?= htmlspecialchars($b['booking_code']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($b['fullname']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($b['email']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($b['phone']) ?></div>
              </td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($b['room_number']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= $typeLabel[$b['room_type']] ?? $b['room_type'] ?> · Floor <?= $b['floor'] ?></div>
              </td>
              <td><?= $durLabel[$b['booking_type']] ?? $b['booking_type'] ?></td>
              <td style="font-size:0.82rem">
                <?= date('d M Y', strtotime($b['start_datetime'])) ?><br>
                <span style="color:var(--text-muted)"><?= date('h:i A', strtotime($b['start_datetime'])) ?></span>
              </td>
              <td style="font-weight:700;color:var(--brown)">RM <?= number_format($b['total_price'], 2) ?></td>
              <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <button class="btn btn-sm btn-outline"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($b)) ?>)">Edit</button>
                  <?php if ($b['status'] === 'active'): ?>
                    <a href="?cancel=<?= $b['id'] ?>&filter=<?= $filter ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Cancel booking <?= htmlspecialchars($b['booking_code']) ?>?')">
                       Cancel
                    </a>
                  <?php endif; ?>
                  <a href="?delete=<?= $b['id'] ?>&filter=<?= $filter ?>"
                     class="btn btn-sm"
                     style="background:#6c757d;color:#fff"
                     onclick="return confirm('Permanently delete this booking? This cannot be undone.')">
                     Delete
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div><!-- /admin-content -->
  </div><!-- /admin-main -->
</div>

<!-- ── Add Booking Modal ─────────────────────── -->
<div class="modal-bg" id="addModal">
  <div class="modal-box">
    <h3 style="margin-bottom:20px">➕ Add Booking</h3>
    <form method="POST">
      <input type="hidden" name="add_booking" value="1">
      <div class="form-group">
        <label>Customer *</label>
        <select name="user_id" required>
          <option value="">— Select Customer —</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['fullname']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Workspace / Room *</label>
        <select name="workspace_id" id="wsSelect" required onchange="updatePrice()">
          <option value="">— Select Room —</option>
          <?php foreach ($workspaces as $w): ?>
          <option value="<?= $w['id'] ?>"
            data-slot="<?= $w['price_slot'] ?>"
            data-week="<?= $w['price_week'] ?>"
            data-month="<?= $w['price_month'] ?>"
            data-year="<?= $w['price_year'] ?>">
            <?= htmlspecialchars($w['room_number']) ?> — <?= htmlspecialchars(ucfirst($w['type_name'])) ?> (Floor <?= $w['floor'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Booking Type *</label>
        <select name="booking_type" id="btSelect" onchange="updatePrice()">
          <option value="slot">Slot (4hr)</option>
          <option value="week">Weekly</option>
          <option value="month">Monthly</option>
          <option value="year">Yearly</option>
        </select>
      </div>
      <div class="form-group">
        <label>Start Date & Time *</label>
        <input type="datetime-local" name="start_datetime" required>
      </div>
      <div id="pricePreview" style="display:none;background:var(--cream);border-radius:var(--radius-sm);padding:10px 14px;margin-bottom:14px;font-size:0.88rem;">
        <strong>Estimated Price:</strong> <span id="priceVal" style="color:var(--brown);font-weight:700;"></span>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" rows="2" placeholder="Optional admin notes…"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Create Booking</button>
        <button type="button" class="btn btn-outline" onclick="closeAdd()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── QR Display Modal ─────────────────────── -->
<div class="modal-bg" id="qrModal">
  <div class="modal-box" style="text-align:center">
    <h3>🎫 Booking QR Code</h3>
    <p id="qrLabel" style="color:var(--brown);font-weight:700;margin:8px 0 20px"></p>
    <div id="qrCanvas" style="display:flex;justify-content:center"></div>
    <p style="margin-top:14px;font-size:0.78rem;color:var(--text-muted)">
      Scan to verify this booking at check-in.
    </p>
    <button class="btn btn-primary" style="width:100%;margin-top:16px" onclick="closeQR()">Close</button>
  </div>
</div>

<!-- ── Edit Booking Modal ───────────────────── -->
<div class="modal-bg" id="editModal">
  <div class="modal-box">
    <h3 style="margin-bottom:20px">✏️ Edit Booking</h3>
    <form method="POST">
      <input type="hidden" name="update_booking_id" id="eBID">

      <div style="background:var(--cream);border-radius:var(--radius-sm);padding:14px;margin-bottom:18px;font-size:0.85rem">
        <div><strong>Booking:</strong> <span id="eCode" style="font-family:monospace;color:var(--brown)"></span></div>
        <div><strong>Customer:</strong> <span id="eName"></span></div>
        <div><strong>Room:</strong> <span id="eRoom"></span></div>
      </div>

      <div class="form-group">
        <label>Status *</label>
        <select name="status" id="eStatus">
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="form-group">
        <label>Admin Notes</label>
        <textarea name="notes" id="eNotes" rows="3"
                  placeholder="Internal notes (visible to admins only)"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Save</button>
        <button type="button" class="btn btn-outline" onclick="closeEdit()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── QR Scanner Modal ─────────────────────── -->
<div class="modal-bg" id="scanModal">
  <div class="modal-box">
    <h3 style="margin-bottom:16px">📷 Scan Booking QR</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:14px">
      Point the camera at the user's QR code to look up their booking.
    </p>
    <div id="reader"></div>
    <div id="scanResult" style="display:none;margin-top:14px"></div>
    <button class="btn btn-outline" style="width:100%;margin-top:16px"
            onclick="closeScanner()">Close Scanner</button>
  </div>
</div>

<script>
// ── Add Booking ─────────────────────────────
function openAdd()  { document.getElementById('addModal').classList.add('open'); }
function closeAdd() { document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click', e => { if(e.target===document.getElementById('addModal')) closeAdd(); });

function updatePrice() {
  const ws  = document.getElementById('wsSelect');
  const bt  = document.getElementById('btSelect').value;
  const opt = ws.options[ws.selectedIndex];
  if (!opt || !opt.value) { document.getElementById('pricePreview').style.display='none'; return; }
  const price = opt.getAttribute('data-' + bt);
  const preview = document.getElementById('pricePreview');
  if (price && parseFloat(price) > 0) {
    document.getElementById('priceVal').textContent = 'RM ' + parseFloat(price).toFixed(2) + ' / ' + bt;
    preview.style.display = 'block';
  } else {
    preview.style.display = 'none';
  }
}

// ── QR Display ─────────────────────
let qrInst = null;
function showQR(code) {
  document.getElementById('qrLabel').textContent = code;
  document.getElementById('qrCanvas').innerHTML  = '';
  document.getElementById('qrModal').classList.add('open');
  qrInst = new QRCode(document.getElementById('qrCanvas'), {
    text: code, width: 200, height: 200,
    colorDark: '#1A1A1A', colorLight: '#FFFFFF',
    correctLevel: QRCode.CorrectLevel.H
  });
}
function closeQR() { document.getElementById('qrModal').classList.remove('open'); }
document.getElementById('qrModal').addEventListener('click', function(e) {
  if (e.target === this) closeQR();
});

// ── Edit Booking ────────────────────
function openEdit(b) {
  document.getElementById('eBID').value    = b.id;
  document.getElementById('eCode').textContent = b.booking_code;
  document.getElementById('eName').textContent = b.fullname;
  document.getElementById('eRoom').textContent =
    b.room_number + ' (' + b.room_type.charAt(0).toUpperCase() + b.room_type.slice(1) + ')';
  document.getElementById('eStatus').value = b.status;
  document.getElementById('eNotes').value  = b.notes || '';
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});

// ── QR Scanner ──────────────────────
let scanner = null;
function openScanner() {
  document.getElementById('scanModal').classList.add('open');
  document.getElementById('scanResult').style.display = 'none';
  document.getElementById('scanResult').className     = 'alert alert-info';

  scanner = new Html5Qrcode('reader');
  scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (code) => {
      scanner.stop().catch(() => {});
      const el = document.getElementById('scanResult');
      el.style.display = 'block';
      el.className = 'alert alert-success';
      el.innerHTML =
        '✅ Scanned: <strong>' + code + '</strong><br>' +
        '<a href="manage_booking.php?search=' + encodeURIComponent(code) +
        '" class="btn btn-primary btn-sm" style="display:inline-block;margin-top:10px">' +
        'Look Up This Booking →</a>';
    },
    () => {} // ignore per-frame decode errors
  ).catch((err) => {
    const el = document.getElementById('scanResult');
    el.style.display = 'block';
    el.className = 'alert alert-error';
    el.textContent = '⚠️ Camera error: ' + err;
  });
}
function closeScanner() {
  if (scanner) scanner.stop().catch(() => {});
  scanner = null;
  document.getElementById('scanModal').classList.remove('open');
}
document.getElementById('scanModal').addEventListener('click', function(e) {
  if (e.target === this) closeScanner();
});
</script>
</body>
</html>

