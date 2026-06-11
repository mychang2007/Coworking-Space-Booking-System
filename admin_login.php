<?php
require_once 'auth.php';
require_once 'db.php';

// Already logged in as admin → go to dashboard
if (isLoggedIn() && isAdmin()) {
    header("Location: admin_dashboard.php"); exit;
}
// Logged in as user → go home
if (isLoggedIn()) {
    header("Location: home.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Please enter your admin email and password.';
    } else {
        // FIX: query `staff` table with correct PK `staff_id`
        $st = mysqli_prepare($conn,
            "SELECT staff_id, fullname, email, password, role
             FROM staff WHERE email = ?");
        mysqli_stmt_bind_param($st, 's', $email);
        mysqli_stmt_execute($st);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

        if ($admin && password_verify($pass, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']        = $admin['staff_id'];   // FIX: staff_id not id
            $_SESSION['user_name']      = $admin['fullname'];
            $_SESSION['user_email']     = $admin['email'];
            $_SESSION['user_role']      = 'admin';
            $_SESSION['admin_subrole']  = $admin['role'];       // 'superadmin' or 'staff'

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = 'Incorrect admin email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<style>
.admin-login-page {
    min-height: 100vh;
    background: var(--dark-2);
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    position: relative; overflow: hidden;
}
.admin-login-page::before {
    content: '';
    position: absolute; inset: 0;
    background: url('assets/hero.jpg') center/cover no-repeat;
    opacity: 0.08;
}
.admin-login-box {
    position: relative; z-index: 2;
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 44px 40px;
    width: 100%; max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}
.admin-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--dark-2); color: var(--brown-light);
    padding: 6px 14px; border-radius: 999px;
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; margin-bottom: 20px;
}
.admin-login-box h2 { margin-bottom: 4px; }
.admin-login-box .sub { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 28px; }

.back-link {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.82rem; color: rgba(255,255,255,0.5);
    margin-bottom: 20px; position: relative; z-index: 2;
    text-decoration: none; transition: color .2s;
}
.back-link:hover { color: rgba(255,255,255,0.85); }
</style>
</head>
<body>
<div class="admin-login-page">
  <div style="width:100%;max-width:420px">
    <a href="login.php" class="back-link">← Back to Customer Login</a>

    <div class="admin-login-box">
      <div class="admin-badge">🔑 Admin Portal</div>
      <h2>Staff Login</h2>
      <p class="sub">Enter your admin credentials to access the dashboard.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['logout'])): ?>
        <div class="alert alert-info">👋 You have been logged out.</div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Admin Email</label>
          <input type="email" name="email" placeholder="admin@cowork.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter admin password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;background:var(--dark-2)">
          🔐 Login to Dashboard
        </button>
      </form>

      <p style="text-align:center;margin-top:20px;font-size:0.82rem;color:var(--text-muted)">
        Not staff? <a href="login.php" style="color:var(--brown);font-weight:600">Customer login →</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
