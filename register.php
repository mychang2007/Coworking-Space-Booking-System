<?php
require_once 'auth.php';
require_once 'db.php';
if (isLoggedIn()) { header("Location: home.php"); exit; }

$errors = []; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $pass     =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    if (strlen($fullname) < 2)
        $errors[] = 'Full name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10,11}$/', $phone))
        $errors[] = 'Phone must be 10–11 digits, numbers only.';
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/', $pass))
        $errors[] = 'Password needs 8+ chars with a letter, number, and special character (@$!%*#?&).';
    if ($pass !== $confirm)
        $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // FIX: check duplicates in `customer` and `staff` tables
        $c1 = mysqli_prepare($conn, "SELECT customer_id FROM customer WHERE email=?");
        mysqli_stmt_bind_param($c1,'s',$email); mysqli_stmt_execute($c1); mysqli_stmt_store_result($c1);
        $c2 = mysqli_prepare($conn, "SELECT staff_id FROM staff WHERE email=?");
        mysqli_stmt_bind_param($c2,'s',$email); mysqli_stmt_execute($c2); mysqli_stmt_store_result($c2);

        if (mysqli_stmt_num_rows($c1) || mysqli_stmt_num_rows($c2)) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // FIX: insert into `customer` table
            $ins  = mysqli_prepare($conn,
                "INSERT INTO customer (fullname, email, phone, password) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($ins,'ssss',$fullname,$email,$phone,$hash);
            if (mysqli_stmt_execute($ins)) {
                $success = 'Account created! You can now log in.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — CoWork Space</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="home.php" class="navbar-logo">CO<span>WORK</span></a>
  <div class="navbar-actions">
    <a href="login.php" class="btn btn-outline btn-sm">Login</a>
  </div>
</nav>

<div class="form-page">
  <div class="form-container">
    <h2>Create Account</h2>
    <p class="subtitle">Join CoWork Space and book your workspace today.</p>

    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= $success ?>
        <a href="login.php" style="font-weight:700;color:inherit"> Login now →</a>
      </div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="fullname"
               value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
               placeholder="e.g. Ahmad Bin Ali" required>
      </div>
      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@email.com" required>
      </div>
      <div class="form-group">
        <label>Phone Number *</label>
        <input type="tel" name="phone"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               placeholder="0123456789" maxlength="11" required>
        <span class="form-hint">10–11 digits, no dashes or spaces</span>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" id="pw"
               placeholder="Min 8 chars with letter, number, special char" required>
        <div class="strength-bar"><div class="strength-fill" id="sf"></div></div>
        <span class="form-hint" id="sl">Must include letter, number &amp; special character</span>
      </div>
      <div class="form-group">
        <label>Confirm Password *</label>
        <input type="password" name="confirm" id="cpw"
               placeholder="Re-enter your password" required>
        <span class="form-error" id="me" style="display:none">Passwords do not match</span>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
        Create Account
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--text-muted)">
      Already have an account?
      <a href="login.php" style="color:var(--brown);font-weight:600">Log in</a>
    </p>
  </div>
</div>

<script>
const pw=document.getElementById('pw'), cpw=document.getElementById('cpw'),
      sf=document.getElementById('sf'), sl=document.getElementById('sl'),
      me=document.getElementById('me');
const C=['#dc3545','#fd7e14','#ffc107','#28a745'];
const L=['Too weak','Fair','Good','Strong ✓'];
pw.addEventListener('input',()=>{
  const v=pw.value; let s=0;
  if(v.length>=8)s++; if(/[A-Za-z]/.test(v))s++;
  if(/\d/.test(v))s++;   if(/[@$!%*#?&]/.test(v))s++;
  sf.style.width=(s/4*100)+'%';
  sf.style.background=C[s-1]||'#e0d8cc';
  sl.textContent=s>0?L[s-1]:'Must include letter, number & special character';
  sl.style.color=C[s-1]||'var(--text-light)';
});
cpw.addEventListener('input',()=>{
  me.style.display=(cpw.value&&cpw.value!==pw.value)?'block':'none';
});
</script>
</body>
</html>
