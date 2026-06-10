<?php
session_start();
require_once 'db.php';

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); exit;
}
$adminName = htmlspecialchars($_SESSION['user_name']);
$adminInit = strtoupper($adminName[0]);

// ── STATS ──────────────────────────────────────
// Active bookings count
$activeCount = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM bookings WHERE status='active'"))['n'];

// Total customers (users with role=user)
$customerCount = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM users WHERE role='user'"))['n'];

// Monthly revenue (current month)
$monthRevenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_price),0) AS r FROM bookings
     WHERE status IN ('active','completed')
     AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"))['r'];

// ── ACTIVE BOOKINGS RIGHT NOW ──────────────────
$liveRes = mysqli_query($conn,
    "SELECT b.*, u.fullname, u.email, u.phone,
            w.room_number, wt.type_name AS room_type, wt.floor
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN workspaces w ON b.workspace_id = w.id
     JOIN workspace_types wt ON w.type_id = wt.id
     WHERE b.status='active'
       AND NOW() BETWEEN b.start_datetime AND b.end_datetime
     ORDER BY b.start_datetime DESC
     LIMIT 20");
$liveBookings = mysqli_fetch_all($liveRes, MYSQLI_ASSOC);

// ── RECENT BOOKINGS ────────────────────────────
$recentRes = mysqli_query($conn,
    "SELECT b.*, u.fullname, w.room_number, wt.type_name AS room_type
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN workspaces w ON b.workspace_id = w.id
     JOIN workspace_types wt ON w.type_id = wt.id
     ORDER BY b.created_at DESC LIMIT 10");
$recentBookings = mysqli_fetch_all($recentRes, MYSQLI_ASSOC);

$typeLabel = ['single'=>'Single','discussion'=>'Discussion','office'=>'Office'];
$durLabel  = ['slot'=>'Slot','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — CoWork Space</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="admin-wrapper">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-logo">CO<span>WORK</span></div>
    <ul class="admin-nav">
      <li class="active"><a href="admin_dashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
      <li><a href="manage_workspace.php"><span class="nav-icon">🏢</span> Manage Workspace</a></li>
      <li><a href="manage_zone.php"><span class="nav-icon">👥</span> Manage Zone</a></li>
      <li><a href="manage_booking.php"><span class="nav-icon">📋</span> Manage Booking</a></li>
    </ul>
    <div style="padding:16px 24px;border-top:1px solid rgba(255,255,255,0.08)">
      <a href="logout.php" class="btn btn-outline btn-sm" style="color:#fff;border-color:rgba(255,255,255,0.3);width:100%">
        Logout
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
      <h4>Dashboard</h4>
      <div class="user-chip">
        <div class="avatar" style="width:30px;height:30px;background:var(--brown);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem"><?= $adminInit ?></div>
        <?= $adminName ?> <span class="badge badge-admin" style="margin-left:6px">Admin</span>
      </div>
    </div>

    <div class="admin-content">
      <h2 style="margin-bottom:24px;font-size:1.2rem">Dashboard Metrics</h2>

      <!-- STAT CARDS -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-label">Active Bookings</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value"><?= $activeCount ?></div>
            <div class="stat-icon stat-icon-brown">📋</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Customers</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value"><?= $customerCount ?></div>
            <div class="stat-icon stat-icon-olive">👥</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Monthly Revenue</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value" style="font-size:1.5rem">RM <?= number_format($monthRevenue, 2) ?></div>
            <div class="stat-icon stat-icon-blue">💰</div>
          </div>
        </div>
      </div>

      <!-- LIVE CHECK-IN BOOKINGS -->
      <div class="admin-table-wrap" style="margin-bottom:24px">
        <h3 style="display:flex;align-items:center;gap:8px">
          <span style="width:10px;height:10px;background:#28a745;border-radius:50%;display:inline-block;animation:pulse 1.5s ease-in-out infinite"></span>
          Live Active Bookings
          <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400">(currently checked in)</span>
          <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted)"><?= count($liveBookings) ?> active</span>
        </h3>

        <?php if (empty($liveBookings)): ?>
          <p style="font-size:0.88rem;color:var(--text-muted);padding:20px 0;text-align:center">No active check-ins right now.</p>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
          <?php foreach ($liveBookings as $lb):
            $rtl = $typeLabel[$lb['room_type']] ?? $lb['room_type'];
          ?>
          <div style="border:1px solid var(--border);border-left:4px solid #28a745;border-radius:var(--radius-sm);padding:16px;background:var(--white)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
              <div>
                <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($lb['booking_code']) ?></div>
                <div style="font-weight:700;font-size:0.95rem;margin-top:2px"><?= htmlspecialchars($lb['fullname']) ?></div>
              </div>
              <span class="badge badge-active">ACTIVE</span>
            </div>
            <div style="font-size:0.82rem;color:var(--text-muted)">
              <div>🏢 Room <?= htmlspecialchars($lb['room_number']) ?> · <?= $rtl ?> · Floor <?= $lb['floor'] ?></div>
              <div>📅 <?= date('d M, h:iA', strtotime($lb['start_datetime'])) ?> → <?= date('h:iA', strtotime($lb['end_datetime'])) ?></div>
              <div>📧 <?= htmlspecialchars($lb['email']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- RECENT BOOKINGS TABLE -->
      <div class="admin-table-wrap">
        <h3>Recent Bookings</h3>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Room</th>
              <th>Type</th>
              <th>Start</th>
              <th>Price</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $rb): ?>
            <tr>
              <td><span style="font-family:monospace;font-size:0.8rem"><?= htmlspecialchars($rb['booking_code']) ?></span></td>
              <td><?= htmlspecialchars($rb['fullname']) ?></td>
              <td><?= htmlspecialchars($rb['room_number']) ?> (<?= $typeLabel[$rb['room_type']] ?>)</td>
              <td><?= $durLabel[$rb['booking_type']] ?? $rb['booking_type'] ?></td>
              <td><?= date('d M Y', strtotime($rb['start_datetime'])) ?></td>
              <td style="font-weight:700;color:var(--brown)">RM <?= number_format($rb['total_price'],2) ?></td>
              <td><span class="badge badge-<?= $rb['status'] ?>"><?= ucfirst($rb['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="margin-top:14px">
          <a href="manage_booking.php" class="btn btn-outline btn-sm">View All Bookings →</a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(40,167,69,0.4); }
  50%      { box-shadow: 0 0 0 6px rgba(40,167,69,0); }
}
</style>
</body>
</html>