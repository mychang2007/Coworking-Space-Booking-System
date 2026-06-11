<?php
// Determine active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-logo">CO<span>WORK</span></div>

  <ul class="admin-nav">
    <li class="<?= $currentPage==='admin_dashboard.php'   ? 'active' : '' ?>">
      <a href="admin_dashboard.php">
        <span class="nav-icon">📊</span> Dashboard
      </a>
    </li>
    <li class="<?= $currentPage==='manage_workspace.php'  ? 'active' : '' ?>">
      <a href="manage_workspace.php">
        <span class="nav-icon">🏢</span> Manage Workspace
      </a>
    </li>
    <li class="<?= $currentPage==='manage_zone.php'        ? 'active' : '' ?>">
      <a href="manage_zone.php">
        <span class="nav-icon">👥</span> Manage Zone
      </a>
    </li>
    <li class="<?= $currentPage==='manage_booking.php'     ? 'active' : '' ?>">
      <a href="manage_booking.php">
        <span class="nav-icon">📋</span> Manage Booking
      </a>
    </li>
  </ul>

  <div style="padding:20px 24px 24px;margin-top:auto;border-top:1px solid rgba(255,255,255,0.08)">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
      <div style="width:34px;height:34px;border-radius:50%;background:var(--brown);
                  display:flex;align-items:center;justify-content:center;
                  color:#fff;font-weight:700;font-size:0.85rem;flex-shrink:0">
        <?= strtoupper(substr($_SESSION['user_name'],0,1)) ?>
      </div>
      <div style="overflow:hidden">
        <div style="font-size:0.85rem;font-weight:600;color:#fff;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($_SESSION['user_name']) ?>
        </div>
        <div style="font-size:0.72rem;color:rgba(255,255,255,0.45)">Administrator</div>
      </div>
    </div>
    <a href="home.php"   class="btn btn-sm" style="width:100%;background:rgba(255,255,255,0.07);
       color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.12);margin-bottom:6px">
      🏠 View Site
    </a>
    <a href="logout.php" class="btn btn-sm" style="width:100%;background:rgba(220,53,69,0.15);
       color:#f88;border:1px solid rgba(220,53,69,0.25)">
      🚪 Logout
    </a>
  </div>
</aside>