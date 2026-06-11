<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$userId   = (int)$_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name']);
$userInit = strtoupper($userName[0]);

// FIX: Cancel uses booking_id, customer_id
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bid = (int)$_GET['cancel'];
    mysqli_query($conn,
        "UPDATE booking SET status='cancelled'
         WHERE booking_id=$bid AND customer_id=$userId");
    header("Location: my_booking.php"); exit;
}

// FIX: query `booking` table with ERD columns, JOIN workspace + zone
$q = "
  SELECT b.booking_id AS id,
         b.booking_token AS booking_code,
         b.booking_type,
         b.start_time   AS start_datetime,
         b.end_time     AS end_datetime,
         b.total_price,
         b.status,
         b.notes,
         b.created_at,
         w.workspace_name AS room_number,
         z.zone_name  AS room_type,
         z.floor
  FROM booking b
  JOIN workspace w ON b.workspace_id = w.workspace_id
  JOIN zone      z ON w.zone_id      = z.zone_id
  WHERE b.customer_id = $userId
  ORDER BY b.created_at DESC
";
$res      = mysqli_query($conn, $q);
$bookings = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
.booking-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.detail-item { font-size: 0.82rem; }
.detail-item .label { color: var(--text-muted); margin-bottom: 2px; }
.detail-item .value { font-weight: 600; color: var(--dark); }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state .icon { font-size: 3rem; margin-bottom: 16px; }
</style>
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-logo">CO<span>WORK</span></a>
  <ul class="navbar-links">
    <li><a href="home.php">Home</a></li>
    <li><a href="workspace_list.php">Workspaces</a></li>
    <li><a href="booking_form.php">Book Now</a></li>
    <li><a href="my_booking.php" style="color:var(--brown)">My Bookings</a></li>
  </ul>
  <div class="navbar-actions">
    <div class="user-dropdown">
      <div class="user-pill" onclick="toggleDropdown(event)">
        <div class="avatar"><?= $userInit ?></div>
          <span><?= $userName ?></span>
          <span class="arrow">▼</span>
        </div>
      <div class="dropdown-menu" id="userMenu">
        <a href="settings.php">⚙️ Account Settings</a>
        <div class="dropdown-divider"></div>
        <script src="dropdown.js"></script>
    <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
  </div>
</nav>

<div class="my-bookings-page">
  <div class="my-bookings-inner">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.8rem">My Bookings</h1>
        <p style="margin-top:4px">You have <?= count($bookings) ?> booking(s) total</p>
      </div>
      <a href="booking_form.php" class="btn btn-primary">+ New Booking</a>
    </div>

    <?php if (isset($_GET['booked'])): ?>
    <div class="alert alert-success">🎉 Booking confirmed! Your QR code is ready below.</div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
    <div class="booking-panel empty-state">
      <div class="icon">📋</div>
      <h3>No bookings yet</h3>
      <p>Book your first workspace and your details will appear here.</p>
      <a href="booking_form.php" class="btn btn-primary" style="margin-top:16px">Book Now</a>
    </div>
    <?php else: ?>

    <?php
    $types = [
    'pending checkin' => 'Pending Check-in', 
    'active' => 'Active', 
    'checkout late' => 'Checkout Late', 
    'completed' => 'Completed', 
    'cancelled' => 'Cancelled'
    ];

    foreach ($types as $status => $label):
      $group = array_filter($bookings, fn($b) => $b['status'] === $status);
      if (empty($group)) continue;
    ?>
    <h3 style="margin:24px 0 12px;font-size:0.95rem;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted)"><?= $label ?></h3>

    <?php foreach ($group as $bk):
      $badge     = $bk['status'];
      $typeLabel = ['Single Room'=>'Single Room','Discussion Room'=>'Discussion Room','Private Office'=>'Private Office'][$bk['room_type']] ?? $bk['room_type'];
      $durLabel  = ['slot'=>'4-Hour Slot','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'][$bk['booking_type']] ?? $bk['booking_type'];
    ?>
    <div class="booking-card">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap">
          <span class="booking-id-link" onclick="showQR('<?= $bk['booking_code'] ?>')">
            🎫 <?= htmlspecialchars($bk['booking_code']) ?>
          </span>
          <span class="badge badge-<?= $badge ?>"><?= ucfirst($bk['status']) ?></span>
          <span style="font-size:0.78rem;color:var(--text-muted)">(click ID for QR)</span>
        </div>

        <div class="booking-card-grid">
          <div class="detail-item">
            <div class="label">Workspace</div>
            <div class="value"><?= htmlspecialchars($typeLabel) ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Room</div>
            <div class="value"><?= htmlspecialchars($bk['room_number']) ?> · Floor <?= $bk['floor'] ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Check-in</div>
            <div class="value"><?= date('d M Y, h:i A', strtotime($bk['start_datetime'])) ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Check-out</div>
            <div class="value"><?= date('d M Y, h:i A', strtotime($bk['end_datetime'])) ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Duration Type</div>
            <div class="value"><?= $durLabel ?></div>
          </div>
          <div class="detail-item">
            <div class="label">Total Paid</div>
            <div class="value" style="color:var(--brown)">RM <?= number_format($bk['total_price'], 2) ?></div>
          </div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
        <button class="btn btn-olive btn-sm" onclick="showQR('<?= $bk['booking_code'] ?>')">📱 QR</button>
        <?php if ($bk['status'] === 'active' || $bk['status'] === 'pending checkin'): ?>
        <a href="my_booking.php?cancel=<?= $bk['id'] ?>"
           onclick="return confirm('Cancel this booking?')"
           class="btn btn-danger btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>

    <?php endif; ?>
  </div>
</div>

<!-- QR MODAL -->
<div id="qrOverlay" class="qr-modal-overlay" style="display:none" onclick="closeQR(event)">
  <div class="qr-modal">
    <h3>Check-In QR Code</h3>
    <p id="qrCodeLabel" style="color:var(--brown);font-weight:700">—</p>
    <div id="qrCanvas" style="display:flex;justify-content:center"></div>
    <p style="margin-top:12px;font-size:0.78rem;color:var(--text-muted)">
      Show this QR at the counter to check in. Each code is unique to your booking.
    </p>
    <button class="btn btn-primary close-btn" onclick="closeQR()">Close</button>
  </div>
</div>

<script>
let qrInstance = null;
function showQR(code) {
  document.getElementById('qrOverlay').style.display = 'flex';
  document.getElementById('qrCodeLabel').textContent = code;
  const canvas = document.getElementById('qrCanvas');
  canvas.innerHTML = '';
  qrInstance = new QRCode(canvas, {
    text: code, width: 200, height: 200,
    colorDark: '#1A1A1A', colorLight: '#FFFFFF',
    correctLevel: QRCode.CorrectLevel.H
  });
}
function closeQR(e) {
  if (!e || e.target.id === 'qrOverlay' || e.currentTarget.tagName === 'BUTTON') {
    document.getElementById('qrOverlay').style.display = 'none';
    document.getElementById('qrCanvas').innerHTML = '';
  }
}
</script>
</body>
</html>
