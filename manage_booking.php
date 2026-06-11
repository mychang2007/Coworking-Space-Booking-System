<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

// --- NEW: AUTOMATICALLY SWEEP EXPIRED NO-SHOWS TO COMPLETED ---
// If a booking is still 'pending checkin' but the end_time has passed, auto-complete it.
mysqli_query($conn, "
    UPDATE booking 
    SET status = 'completed', 
        notes = TRIM(CONCAT(IFNULL(notes, ''), '\n[System: Automatically completed - No check-in recorded by slot end time.]')) 
    WHERE status = 'pending checkin' 
      AND end_time < NOW()
");

$adminName = htmlspecialchars($_SESSION['user_name']);

// --- GRAB MESSAGES FROM SESSION (Prevents refresh issues) ---
$msg = $_SESSION['msg'] ?? ''; 
$err = $_SESSION['err'] ?? '';
unset($_SESSION['msg'], $_SESSION['err']);

// --- AUTOMATED QR CHECK-IN / CHECK-OUT PROCESSING ---
if (isset($_GET['scan_token'])) {
    $token = trim($_GET['scan_token']);
    $stmt = mysqli_prepare($conn, "SELECT booking_id, status, end_time, total_price, notes FROM booking WHERE booking_token = ?");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $bData = mysqli_fetch_assoc($res);

    if (!$bData) {
        $_SESSION['err'] = "Invalid QR Code. Booking token not found.";
    } else {
        $bid = $bData['booking_id'];
        $currentStatus = $bData['status'];
        $endTime = strtotime($bData['end_time']);
        $currentTime = time(); 

        if ($currentStatus === 'pending checkin') {
            // Step 1: Handle Check-in
            $up = mysqli_prepare($conn, "UPDATE booking SET status='active', checkin_time=NOW() WHERE booking_id=?");
            mysqli_stmt_bind_param($up, 'i', $bid);
            if (mysqli_stmt_execute($up)) {
                $_SESSION['msg'] = "✅ Check-in successful for Token: " . htmlspecialchars($token);
            } else {
                $_SESSION['err'] = "Database error occurred during check-in processing.";
            }
        } elseif ($currentStatus === 'active') {
            // Step 2: Handle Check-out
            if ($currentTime <= $endTime) {
                // On-Time Check-out
               $up = mysqli_prepare($conn, "UPDATE booking SET status='completed', checkout_time=NOW() WHERE booking_id=?");
                mysqli_stmt_bind_param($up, 'i', $bid);
                if (mysqli_stmt_execute($up)) {
                    $_SESSION['msg'] = "✅ On-time check-out processed successfully for Token: " . htmlspecialchars($token);
                } else {
                    $_SESSION['err'] = "Database error occurred during check-out processing.";
                }
            } else {
                // Late Check-out Calculation (RM1 per 15 minutes or part thereof)
                $diffSeconds = $currentTime - $endTime;
                $diffMinutes = ceil($diffSeconds / 60);
                $intervals   = ceil($diffMinutes / 15);
                $lateCharge  = $intervals * 1.00;
                
                $newPrice = $bData['total_price'] + $lateCharge;
                $systemNote = "[System: Late check-out by " . $diffMinutes . " mins. Charged extra RM " . number_format($lateCharge, 2) . "]";
                $newNotes = trim($bData['notes'] . "\n" . $systemNote);
                
                $up = mysqli_prepare($conn, "UPDATE booking SET status='checkout late', total_price=?, notes=?, checkout_time=NOW() WHERE booking_id=?");
                mysqli_stmt_bind_param($up, 'dsi', $newPrice, $newNotes, $bid);
                if (mysqli_stmt_execute($up)) {
                    $_SESSION['msg'] = "⚠️ CheckOut Late processed! Overdue by " . $diffMinutes . " mins. Extra fee of RM " . number_format($lateCharge, 2) . " added.";
                } else {
                    $_SESSION['err'] = "Failed to calculate and update late check-out data.";
                }
            }
        } elseif (in_array($currentStatus, ['completed', 'checkout late'])) {
            $_SESSION['err'] = "This booking token has already completed its check-out cycle.";
        } elseif ($currentStatus === 'cancelled') {
            $_SESSION['err'] = "This booking has been cancelled and cannot be used for check-in.";
        } else {
            $_SESSION['err'] = "Unknown status execution block encountered.";
        }
    }
    
    // REDIRECT TO CLEAR THE URL AFTER SCANNING
    header("Location: manage_booking.php");
    exit;
}

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    mysqli_query($conn,
        "UPDATE booking SET status='cancelled' WHERE booking_id=".(int)$_GET['cancel']);
    $_SESSION['msg'] = "Booking cancelled successfully.";
    header("Location: manage_booking.php"); exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    mysqli_query($conn,
        "DELETE FROM booking WHERE booking_id=".(int)$_GET['delete']);
    $_SESSION['msg'] = "Booking permanently deleted.";
    header("Location: manage_booking.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_id'])) {
    $bid    = (int)$_POST['update_booking_id'];
    $status = in_array($_POST['status'] ?? '', ['pending checkin', 'active', 'completed', 'checkout late', 'cancelled'])
              ? $_POST['status'] : 'pending checkin';
    $notes  = trim($_POST['notes'] ?? '');
    $st     = mysqli_prepare($conn,
                "UPDATE booking SET status=?, notes=? WHERE booking_id=?");
    mysqli_stmt_bind_param($st, 'ssi', $status, $notes, $bid);
    
    if (mysqli_stmt_execute($st)) {
        $_SESSION['msg'] = 'Booking updated successfully.';
    } else {
        $_SESSION['err'] = 'Update failed.';
    }
    
    // REDIRECT TO CLEAR POST DATA ON REFRESH
    header("Location: manage_booking.php"); exit;
}

$allowedFilters = ['all', 'pending checkin', 'active', 'completed', 'checkout late', 'cancelled'];
$filter = in_array($_GET['filter'] ?? '', $allowedFilters) ? ($_GET['filter'] ?? 'all') : 'all';
$search = trim($_GET['search'] ?? '');

// ─── NEW: Capture workspace filter from URL ─────────────────
$workspace_id = isset($_GET['workspace_id']) && is_numeric($_GET['workspace_id']) ? (int)$_GET['workspace_id'] : 0;

$where = "WHERE 1=1";
if ($filter !== 'all')
    $where .= " AND b.status='" . mysqli_real_escape_string($conn, $filter) . "'";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (b.booking_token LIKE '%$s%'
                  OR c.fullname LIKE '%$s%'
                  OR c.email LIKE '%$s%')";
}

// ─── NEW: Append workspace condition if requested ──────────
if ($workspace_id) {
    $where .= " AND b.workspace_id = $workspace_id";
}

$bookings = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.booking_id  AS id,
            b.booking_token AS booking_code,
            b.booking_type,
            b.start_time   AS start_datetime,
            b.end_time     AS end_datetime,
            b.total_price,
            b.status,
            b.notes,
            b.created_at,
            c.fullname, c.email, c.phone,
            w.workspace_name AS room_number,
            z.zone_name  AS room_type,
            z.floor
     FROM booking b
     JOIN customer  c ON b.customer_id  = c.customer_id
     JOIN workspace w ON b.workspace_id = w.workspace_id
     JOIN zone      z ON w.zone_id      = z.zone_id
     $where
     ORDER BY b.created_at DESC"), MYSQLI_ASSOC);

$durLabel = ['slot'=>'4-hr Slot','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Booking — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
.badge-pending-checkin { background: #ffeeba; color: #856404; }
.badge-checkout-late { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>
<div class="admin-wrapper">
  <?php include 'admin_sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h4>Manage Booking</h4>
      <div style="display:flex;gap:10px;align-items:center">
        <button class="btn btn-olive btn-sm" onclick="openScanner()">📷 Scan QR</button>
        <div class="user-chip"><?= $adminName ?>
          <span class="badge badge-admin" style="margin-left:6px">Admin</span>
        </div>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <div class="filter-bar">
        <form style="display:flex;gap:8px;flex:1;min-width:220px">
          <input type="text" name="search"
                placeholder="Search booking token, name, email…"
                value="<?= htmlspecialchars($search) ?>"
                style="flex:1;padding:9px 14px;border:1.5px solid var(--border); border-radius:var(--radius-sm);font-size:0.88rem;outline:none">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
          
          <?php if ($workspace_id): ?>
            <input type="hidden" name="workspace_id" value="<?= $workspace_id ?>">
          <?php endif; ?>
          
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
          
          <?php if ($search || $workspace_id): ?>
            <a href="manage_booking.php" class="btn btn-outline btn-sm" style="background:#dc3545; color:#fff; border:none;">✕ Clear Filters</a>
          <?php endif; ?>
        </form>
        
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <?php foreach ($allowedFilters as $f): ?>
          <a href="?filter=<?= $f ?>&search=<?= urlencode($search) ?><?= $workspace_id ? '&workspace_id='.$workspace_id : '' ?>"
            class="btn btn-sm <?= $filter===$f ? 'btn-primary' : 'btn-outline' ?>">
            <?= ucwords($f) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

<?php if ($workspace_id && !empty($bookings)): ?>
  <div class="alert alert-success" style="background: var(--cream); border-color: var(--brown); color: var(--dark); margin-bottom: 20px;">
    📍 Currently filtering entries for Room: <strong><?= htmlspecialchars($bookings[0]['room_number']) ?></strong> 
    (Floor <?= $bookings[0]['floor'] ?>). 
    <a href="manage_booking.php" style="color: #dc3545; font-weight: bold; margin-left: 10px;">Show All Rooms</a>
  </div>
<?php endif; ?>

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
              <th>Booking Token</th>
              <th>Customer</th>
              <th>Room</th>
              <th>Duration</th>
              <th>Schedule</th>
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
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($b['room_type']) ?> · Floor <?= $b['floor'] ?></div>
              </td>
              <td><?= $durLabel[$b['booking_type']] ?? $b['booking_type'] ?></td>
              <td style="font-size:0.82rem; line-height:1.4">
                <div><strong>In:</strong> <?= date('d M Y, h:i A', strtotime($b['start_datetime'])) ?></div>
                <div style="color:var(--text-muted);"><strong>Out:</strong> <?= date('d M Y, h:i A', strtotime($b['end_datetime'])) ?></div>
              </td>
              <td style="font-weight:700;color:var(--brown)">RM <?= number_format($b['total_price'], 2) ?></td>
              <td>
                <?php
                $displayStatus = $b['status'];
                $badgeClass = str_replace(' ', '-', $b['status']);
                $penaltyText = '';

                // Condition 1: Active but current time has run past the designated checkout time
                if ($b['status'] === 'active' && time() > strtotime($b['end_datetime'])) {
                    $displayStatus = 'checkout late';
                    $badgeClass = 'checkout-late';
                } 
                // Condition 2: Already completed checkout late cycle
                elseif ($b['status'] === 'checkout late') {
                    $displayStatus = 'completed';
                    $badgeClass = 'completed';
                    
                    // Extract late penalty charge dynamically from the text note
                    if (!empty($b['notes']) && preg_match('/Charged extra RM\s*([\d.]+)/i', $b['notes'], $matches)) {
                        $penaltyText = '<div style="font-size:0.73rem; color:#dc3545; font-weight:600; margin-top:4px;">(Late Fee: RM ' . number_format((float)$matches[1], 2) . ')</div>';
                    } else {
                        $penaltyText = '<div style="font-size:0.73rem; color:#dc3545; font-weight:600; margin-top:4px;">(Late Penalty Paid)</div>';
                    }
                }
                ?>
                <span class="badge badge-<?= $badgeClass ?>"><?= ucwords($displayStatus) ?></span>
                <?= $penaltyText ?>
              </td>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <button class="btn btn-sm btn-outline"
                    onclick="openEdit(<?= htmlspecialchars(json_encode($b)) ?>)">Edit</button>
                  
                  <?php 
                  // Only allow cancellation if it's pending checkin OR active on-time (not late yet)
                  if ($b['status'] === 'pending checkin' || ($b['status'] === 'active' && time() <= strtotime($b['end_datetime']))): 
                  ?>
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
    </div>
  </div>
</div>

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
          <option value="pending checkin">Pending Checkin</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="checkout late">CheckOut Late</option>
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

<div class="modal-bg" id="scanModal">
  <div class="modal-box">
    <h3 style="margin-bottom:16px">📷 Scan Booking QR</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:14px">
      Point the camera at the user's QR code to run automatic check-in/out processing.
    </p>
    <div id="reader"></div>
    <div id="scanResult" style="display:none;margin-top:14px"></div>
    <button class="btn btn-outline" style="width:100%;margin-top:16px"
            onclick="closeScanner()">Close Scanner</button>
  </div>
</div>

<script>
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

function openEdit(b) {
  document.getElementById('eBID').value            = b.id;
  document.getElementById('eCode').textContent     = b.booking_code;
  document.getElementById('eName').textContent     = b.fullname;
  document.getElementById('eRoom').textContent     = b.room_number + ' (' + b.room_type + ')';
  document.getElementById('eStatus').value         = b.status;
  document.getElementById('eNotes').value          = b.notes || '';
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});

let scanner = null;
function openScanner() {
  document.getElementById('scanModal').classList.add('open');
  document.getElementById('scanResult').style.display = 'none';
  scanner = new Html5Qrcode('reader');
  scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (code) => {
      scanner.stop().catch(() => {});
      window.location.href = "manage_booking.php?scan_token=" + encodeURIComponent(code);
    },
    () => {}
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