<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$userId = (int)$_SESSION['user_id'];
$msg = ''; $err = '';

// Handle information updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn  = trim($_POST['fullname'] ?? '');
    $em  = trim($_POST['email']    ?? '');
    $ph  = trim($_POST['phone']    ?? '');
    $pw  = trim($_POST['password'] ?? '');
    $cpw = trim($_POST['confirm_password'] ?? '');

    if ($fn && filter_var($em, FILTER_VALIDATE_EMAIL) && preg_match('/^[0-9]{10,11}$/', $ph)) {
        // Check if email belongs to someone else
        $emailCheck = mysqli_query($conn, "SELECT customer_id FROM customer WHERE email='".mysqli_real_escape_string($conn, $em)."' AND customer_id != $userId");
        $staffCheck = mysqli_query($conn, "SELECT staff_id FROM staff WHERE email='".mysqli_real_escape_string($conn, $em)."'");
        
        if (mysqli_num_rows($emailCheck) > 0 || mysqli_num_rows($staffCheck) > 0) {
            $err = 'This email address is already in use by another account.';
        } elseif (!empty($pw) && $pw !== $cpw) {
            $err = 'Passwords do not match. Profile update aborted.';
        } else {
            if (!empty($pw)) {
                // Hash the password so it matches your login system encryption
                $hashedPassword = password_hash($pw, PASSWORD_DEFAULT);
                $st = mysqli_prepare($conn, "UPDATE customer SET fullname=?, email=?, phone=?, password=? WHERE customer_id=?");
                mysqli_stmt_bind_param($st, 'ssssi', $fn, $em, $ph, $hashedPassword, $userId);
            } else {
                // Update profile information only
                $st = mysqli_prepare($conn, "UPDATE customer SET fullname=?, email=?, phone=? WHERE customer_id=?");
                mysqli_stmt_bind_param($st, 'sssi', $fn, $em, $ph, $userId);
            }
            
            if (mysqli_stmt_execute($st)) {
                $_SESSION['user_name'] = $fn; // Keep session synced for fallback pages
                $msg = 'Your profile details have been successfully updated!';
            } else {
                $err = 'An error occurred while updating your account metrics.';
            }
        }
    } else {
        $err = 'Invalid profile data inputs. Check your phone number syntax.';
    }
}

// Fetch the most recent data directly from DB to populate the page UI
$custRes  = mysqli_query($conn, "SELECT fullname, email, phone FROM customer WHERE customer_id = $userId");
$user     = mysqli_fetch_assoc($custRes);

// Use database values for presentation layer instead of session strings
$userName = htmlspecialchars($user['fullname'] ?? 'User');
$userInit = strtoupper(!empty($userName) ? $userName[0] : 'U');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Settings — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<style>
.settings-panel { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 32px; max-width: 500px; margin: 0 auto; box-shadow: var(--shadow-sm); }
.meter-wrapper { background: #e0d8cc; height: 6px; border-radius: 3px; width: 100%; overflow: hidden; margin-top: 8px; }
#sf { width: 0%; height: 100%; transition: width 0.3s ease, background 0.3s ease; }
#sl { display: block; margin-top: 5px; font-size: 0.78rem; font-weight: 500; color: var(--text-light); }
#me { display: none; color: #dc3545; margin-top: 5px; font-size: 0.78rem; font-weight: 600; }

/* ── NEW NAVBAR DROPDOWN STYLES ── */
.user-dropdown { position: relative; display: inline-block; }
.user-pill { cursor: pointer; user-select: none; display: flex; align-items: center; gap: 8px; transition: opacity 0.2s ease; }
.user-pill:hover { opacity: 0.85; }
.user-pill .arrow { font-size: 0.65rem; color: var(--text-muted); margin-left: 2px; }

.dropdown-menu { 
    display: none; position: absolute; right: 0; top: 125%; 
    background: #fff; border: 1px solid var(--border); border-radius: var(--radius-sm); 
    min-width: 170px; box-shadow: var(--shadow-md); z-index: 1000; padding: 6px 0; 
}
.dropdown-menu.show { display: block; }
.dropdown-menu a { 
    display: flex; align-items: center; gap: 8px; padding: 10px 16px; 
    color: var(--text-dark); text-decoration: none; font-size: 0.88rem; 
    transition: background 0.2s ease; text-align: left; 
}
.dropdown-menu a:hover { background: #fdfaf4; color: var(--brown); }
.dropdown-divider { height: 1px; background: var(--border); margin: 6px 0; }
</style>
</head>
<body>

<nav class="navbar">
  <a href="home.php" class="navbar-logo">CO<span>WORK</span></a>
  <ul class="navbar-links">
    <li><a href="home.php">Home</a></li>
    <li><a href="workspace_list.php">Workspaces</a></li>
    <li><a href="booking_form.php">Book Now</a></li>
    <li><a href="my_booking.php">My Bookings</a></li>
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
        <a href="logout.php" style="color:#dc3545;">🚪 Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="my-bookings-page" style="padding-top:40px">
  <div class="my-bookings-inner">
    <div style="margin-bottom:24px; text-align:center;">
        <h1 style="font-size:1.8rem">Account Settings</h1>
        <p style="margin-top:4px; color:var(--text-muted)">Manage and update your personal workspace credentials.</p>
    </div>

    <div class="settings-panel">
      <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom:18px"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error" style="margin-bottom:18px">⚠️ <?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="POST" id="settingsForm">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
          <label>Phone Number</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" maxlength="11" required>
          <span class="form-hint">10–11 digits, numbers only</span>
        </div>

        <div class="form-group">
          <label>Change Password (leave blank to keep current)</label>
          <input type="password" name="password" id="pw" placeholder="Enter new password">
          <div class="meter-wrapper">
             <div id="sf"></div>
          </div>
          <small id="sl">Must include letter, number & special character</small>
        </div>

        <div class="form-group" style="margin-top: 16px;">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" id="cpw" placeholder="Verify your new password">
          <small id="me">⚠️ Passwords do not match</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:20px; padding: 12px;">Save Profile Updates</button>
      </form>
    </div>
  </div>
</div>

<script>
const pw=document.getElementById('pw'), cpw=document.getElementById('cpw'),
      sf=document.getElementById('sf'), sl=document.getElementById('sl'),
      me=document.getElementById('me'), form=document.getElementById('settingsForm');
      
const C=['#dc3545','#fd7e14','#ffc107','#28a745'];
const L=['Too weak','Fair','Good','Strong ✓'];

pw.addEventListener('input',()=>{
  const v=pw.value; let s=0;
  if(!v) {
    sf.style.width='0%';
    sl.textContent='Must include letter, number & special character';
    sl.style.color='var(--text-light)';
    return;
  }
  if(v.length>=8)s++; if(/[A-Za-z]/.test(v))s++;
  if(/\d/.test(v))s++;   if(/[@$!%*#?&]/.test(v))s++;
  sf.style.width=(s/4*100)+'%';
  sf.style.background=C[s-1]||'#e0d8cc';
  sl.textContent=s>0?L[s-1]:'Must include letter, number & special character';
  sl.style.color=C[s-1]||'var(--text-light)';
  
  me.style.display=(cpw.value && cpw.value!==pw.value)?'block':'none';
});

cpw.addEventListener('input',()=>{
  me.style.display=(cpw.value&&cpw.value!==pw.value)?'block':'none';
});

form.addEventListener('submit',(e)=>{
  if(pw.value && pw.value !== cpw.value) {
    e.preventDefault();
    me.style.display='block';
    cpw.focus();
  }
});
</script>
</body>
</html>