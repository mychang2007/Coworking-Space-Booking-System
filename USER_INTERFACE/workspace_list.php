<?php
require_once 'auth.php';
require_once 'db.php';
$loggedIn    = isLoggedIn();
$userName    = $loggedIn ? htmlspecialchars($_SESSION['user_name']) : '';
$userInitial = $loggedIn ? strtoupper($userName[0]) : '';

// FIX: query `zone` table (was workspace_types).
// avail_rooms = rooms whose workspace_id has NO active booking overlapping NOW.
// This reflects real-time availability instead of the static `status` column.
$types = mysqli_fetch_all(mysqli_query($conn,
    "SELECT z.*,
        COUNT(w.workspace_id) AS total_rooms,
        SUM(
            w.status = 'available'
            AND NOT EXISTS (
                SELECT 1 FROM booking b
                WHERE b.workspace_id = w.workspace_id
                  AND b.status IN ('active','Pending CheckIn')
                  AND NOW() BETWEEN b.start_time AND b.end_time
            )
        ) AS avail_rooms
     FROM zone z
     LEFT JOIN workspace w ON w.zone_id = z.zone_id
     GROUP BY z.zone_id
     ORDER BY z.floor"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Workspaces — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<style>
.ws-hero{padding:120px 5% 60px;background:var(--cream);text-align:center}
.ws-hero h1 span{color:var(--olive-light)}
.ws-block{padding:60px 5%}
.ws-block:nth-child(even){background:var(--cream)}
.ws-row{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;max-width:1100px;margin:0 auto}
.ws-row.rev{direction:rtl}
.ws-row.rev>*{direction:ltr}
.ws-img{border-radius:var(--radius-lg);overflow:hidden;height:300px;background:var(--cream-dark);position:relative}
.ws-img img{width:100%;height:100%;object-fit:cover;display:block}
.floor-tag{position:absolute;top:16px;left:16px;background:var(--brown);color:#fff;
    padding:5px 14px;border-radius:999px;font-size:.78rem;font-weight:700}
.avail-tag{position:absolute;top:16px;right:16px;background:rgba(0,0,0,.55);color:#fff;
    padding:5px 12px;border-radius:999px;font-size:.75rem;font-weight:600}
.avail-pill{display:inline-block;background:#d4edda;color:#155724;
    padding:4px 12px;border-radius:999px;font-size:.8rem;font-weight:600;margin-bottom:14px}
.price-card{background:var(--white);border:1px solid var(--border);
    border-radius:var(--radius);padding:18px 20px;margin-bottom:20px}
.prow{display:flex;justify-content:space-between;padding:8px 0;
    border-bottom:1px solid var(--border);font-size:.88rem}
.prow:last-child{border-bottom:none}
.prow .pr{font-weight:800;color:var(--brown);font-size:1rem}
@media(max-width:768px){.ws-row,.ws-row.rev{grid-template-columns:1fr;direction:ltr}}
</style>
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-logo">CO<span>WORK</span></a>
  <ul class="navbar-links">
    <li><a href="home.php">Home</a></li>
    <li><a href="workspace_list.php" style="color:var(--brown)">Workspaces</a></li>
    <li><a href="booking_form.php">Book Now</a></li>
    <?php if ($loggedIn && isUser()): ?>
    <li><a href="my_booking.php">My Bookings</a></li>
    <?php endif; ?>
  </ul>
  <div class="navbar-actions">
    <?php if ($loggedIn): ?>
      <div class="user-dropdown">
        <div class="user-pill" onclick="toggleDropdown(event)">
          <div class="avatar"><?= $userInitial ?></div>
          <span><?= $userName ?></span>
          <span class="arrow">▼</span>
        </div>
        <div class="dropdown-menu" id="userMenu">
          <a href="settings.php">⚙️ Account Settings</a>
          <div class="dropdown-divider"></div>
          <script src="dropdown.js"></script>
      <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php"    class="btn btn-outline btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary  btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="ws-hero">
  <h1>Our <span>Workspaces</span></h1>
  <p>Three floors, three experiences — each designed for a different way of working.</p>
</div>

<?php
$imgs = [1=>'assets/Single.jpg', 2=>'assets/Discussion.jpg', 3=>'assets/Office.jpg'];
foreach ($types as $i => $t):
    $rev   = $i % 2 === 1 ? 'rev' : '';
    $img   = $imgs[$t['floor']] ?? 'assets/gallery1.jpg';
    $avail = (int)$t['avail_rooms'];
    $total = (int)$t['total_rooms'];
    // FIX: use zone_id for booking link
    $zoneSlug = strtolower(str_replace(' ','-',$t['zone_name']));
?>
<section class="ws-block" id="<?= $zoneSlug ?>">
  <div class="ws-row <?= $rev ?>">
    <div class="ws-img">
      <img src="<?= $img ?>" alt="<?= htmlspecialchars($t['zone_name']) ?>">
      <span class="floor-tag">Floor <?= $t['floor'] ?></span>
      <span class="avail-tag"><?= $avail ?>/<?= $total ?> available</span>
    </div>
    <div class="ws-info">
      <h2><?= htmlspecialchars($t['zone_name']) ?></h2>
      <span class="avail-pill">🟢 <?= $avail ?> room<?= $avail !== 1 ? 's' : '' ?> available now</span>
      <p><?= htmlspecialchars($t['description'] ?? '') ?></p>
      <div class="price-card">
        <?php if ($t['price_slot']): ?>
          <div class="prow"><span>Per Slot (4 hours)</span><span class="pr">RM <?= number_format($t['price_slot'],2) ?></span></div>
        <?php endif; ?>
        <?php if ($t['price_week']): ?>
          <div class="prow"><span>Weekly</span> <span class="pr">RM <?= number_format($t['price_week'],2) ?></span></div>
        <?php endif; ?>
        <?php if ($t['price_month']): ?>
          <div class="prow"><span>Monthly</span><span class="pr">RM <?= number_format($t['price_month'],2) ?></span></div>
        <?php endif; ?>
        <?php if ($t['price_year']): ?>
          <div class="prow"><span>Yearly</span> <span class="pr">RM <?= number_format($t['price_year'],2) ?></span></div>
        <?php endif; ?>
      </div>
      <!-- FIX: pass zone_id instead of type_id -->
      <a href="booking_form.php?zone_id=<?= $t['zone_id'] ?>" class="btn btn-primary btn-lg">
        Book <?= htmlspecialchars($t['zone_name']) ?>
      </a>
    </div>
  </div>
</section>
<?php endforeach; ?>

<footer>
  <div><div class="logo">CO<span style="color:var(--brown-light)">WORK</span></div></div>
  <p style="font-size:.8rem">&copy; <?= date('Y') ?> CoWork Space.</p>
</footer>
</body>
</html>
