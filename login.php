<?php
require_once 'auth.php';
require_once 'db.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? 'admin_dashboard.php' : 'home.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  =      $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Please enter your email and password.';
    } else {
        // FIX: query `customer` table with correct PK `customer_id`
        $st = mysqli_prepare($conn,
            "SELECT customer_id, fullname, email, password, status
             FROM customer WHERE email = ?");
        mysqli_stmt_bind_param($st, 's', $email);
        mysqli_stmt_execute($st);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

        if ($user && password_verify($pass, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Please contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['customer_id'];  // FIX: customer_id not id
                $_SESSION['user_name']  = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = 'user';

                $redirect = $_GET['redirect'] ?? 'home.php';
                header("Location: " . ltrim($redirect, '/'));
                exit;
            }
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — CoWork Space</title>
<link rel="stylesheet" href="style.css">
<style>
.login-split {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.login-left {
    background: var(--dark-2);
    display: flex; align-items: center; justify-content: center;
    padding: 48px;
    position: relative; overflow: hidden;
}
.login-left::before {
    content: '';
    position: absolute; inset: 0;
    background: url('assets/hero.jpg') center/cover no-repeat;
    opacity: 0.25;
}
.login-left-content {
    position: relative; z-index: 2; color: #fff; text-align: center;
}
.login-left-content .logo {
    font-size: 2.5rem; font-weight: 800;
    letter-spacing: 0.12em; color: #fff; margin-bottom: 16px;
}
.login-left-content .logo span { color: var(--brown-light); }
.login-left-content p {
    color: rgba(255,255,255,0.65); font-size: 0.95rem; max-width: 280px;
}
.login-right {
    display: flex; align-items: center; justify-content: center;
    background: var(--white); padding: 48px 40px;
}
.login-box { width: 100%; max-width: 400px; }
.login-box h2 { margin-bottom: 4px; }
.login-box .sub { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 28px; }

.admin-link-bar {
    margin-top: 24px;
    padding: 16px;
    background: var(--cream);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    text-align: center;
}
.admin-link-bar p { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 10px; }

@media (max-width: 700px) {
    .login-split { grid-template-columns: 1fr; }
    .login-left  { display: none; }
    .login-right { padding: 40px 24px; }
}
</style>
</head>
<body>
<div class="login-split">
  <!-- LEFT PANEL -->
  <div class="login-left">
    <div class="login-left-content">
      <div class="logo">CO<span>WORK</span></div>
      <p>Your productive escape. Book a workspace, focus deeply, get things done.</p>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="login-right">
    <div class="login-box">
      <h2>Welcome Back</h2>
      <p class="sub">Log in to your customer account.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">✅ Account created! Please log in.</div>
      <?php endif; ?>
      <?php if (isset($_GET['logout'])): ?>
        <div class="alert alert-info">👋 You have been logged out.</div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="Enter your email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required autofocus>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px">
          Log In
        </button>
      </form>

      <p style="text-align:center;margin-top:18px;font-size:0.88rem;color:var(--text-muted)">
        Don't have an account?
        <a href="register.php" style="color:var(--brown);font-weight:600">Register here</a>
      </p>

      <!-- Admin shortcut -->
      <div class="admin-link-bar">
        <p>Are you a staff member or administrator?</p>
        <a href="admin_login.php" class="btn btn-outline" style="width:100%">
          🔑 Go to Admin Login
        </a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
