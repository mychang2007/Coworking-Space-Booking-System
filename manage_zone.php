<?php
require_once 'auth.php';
require_once 'db.php';
requireAdmin();

$adminName = htmlspecialchars($_SESSION['user_name']);
$selfId    = (int)$_SESSION['user_id'];
$msg = ''; $err = '';

// ── ADD NEW CUSTOMER ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $fn   = trim($_POST['fullname'] ?? '');
    $em   = trim($_POST['email']    ?? '');
    $ph   = trim($_POST['phone']    ?? '');
    $pw   = trim($_POST['password'] ?? '');

    if ($fn && filter_var($em, FILTER_VALIDATE_EMAIL) && preg_match('/^[0-9]{10,11}$/', $ph) && $pw) {
        // Check duplicate email
        $check1 = mysqli_query($conn, "SELECT email FROM customer WHERE email='".mysqli_real_escape_string($conn, $em)."'");
        $check2 = mysqli_query($conn, "SELECT email FROM staff WHERE email='".mysqli_real_escape_string($conn, $em)."'");
        
        if (mysqli_num_rows($check1) > 0 || mysqli_num_rows($check2) > 0) {
            $err = 'This email address is already registered.';
        } else {
            $st = mysqli_prepare($conn, "INSERT INTO customer (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($st, 'ssss', $fn, $em, $ph, $pw);
            mysqli_stmt_execute($st) ? $msg = 'New customer successfully created!' : $err = 'Failed to create customer.';
        }
    } else {
        $err = 'Invalid input data. Phone must be 10-11 digits without spaces.';
    }
}

// ── PROMOTE: normal customer → staff ──────────────
if (isset($_GET['promote']) && is_numeric($_GET['promote'])) {
    $uid = (int)$_GET['promote'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customer WHERE customer_id=$uid"));
    if ($row) {
        $st = mysqli_prepare($conn, "INSERT IGNORE INTO staff (fullname, email, phone, password, role, promoted_by) VALUES (?,?,?,?,'staff',?)");
        mysqli_stmt_bind_param($st,'ssssi', $row['fullname'],$row['email'],$row['phone'],$row['password'],$selfId);
        mysqli_stmt_execute($st);
        mysqli_query($conn, "DELETE FROM customer WHERE customer_id=$uid");
    }
    header("Location: manage_zone.php"); exit;
}

// ── DEMOTE: staff → customer ───────────────────
if (isset($_GET['demote']) && is_numeric($_GET['demote'])) {
    $uid = (int)$_GET['demote'];
    if ($uid !== $selfId) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE staff_id=$uid"));
        if ($row) {
            $st = mysqli_prepare($conn, "INSERT IGNORE INTO customer (fullname, email, phone, password) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($st,'ssss', $row['fullname'],$row['email'],$row['phone'],$row['password']);
            mysqli_stmt_execute($st);
            mysqli_query($conn, "DELETE FROM staff WHERE staff_id=$uid");
        }
    }
    header("Location: manage_zone.php"); exit;
}

// ── DELETE ─────────────────────────────────────
if (isset($_GET['delete'])) {
    $uid  = (int)$_GET['delete'];
    $type = $_GET['type'] ?? 'user';
    if (!($type === 'admin' && $uid === $selfId)) {
        if ($type === 'admin') {
            mysqli_query($conn, "DELETE FROM staff WHERE staff_id=$uid");
        } else {
            mysqli_query($conn, "DELETE FROM customer WHERE customer_id=$uid");
        }
    }
    header("Location: manage_zone.php"); exit;
}

// ── UPDATE USER (POST) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $uid  = (int)$_POST['edit_user_id'];
    $type = $_POST['edit_type'] ?? 'user';
    $fn   = trim($_POST['fullname'] ?? '');
    $em   = trim($_POST['email']    ?? '');
    $ph   = trim($_POST['phone']    ?? '');

    if ($fn && filter_var($em, FILTER_VALIDATE_EMAIL) && preg_match('/^[0-9]{10,11}$/', $ph)) {
        if ($type === 'admin') {
            $st = mysqli_prepare($conn, "UPDATE staff SET fullname=?, email=?, phone=? WHERE staff_id=?");
        } else {
            $st = mysqli_prepare($conn, "UPDATE customer SET fullname=?, email=?, phone=? WHERE customer_id=?");
        }
        mysqli_stmt_bind_param($st,'sssi',$fn,$em,$ph,$uid);
        mysqli_stmt_execute($st) ? $msg = 'User updated.' : $err = 'Update failed.';
    } else {
        $err = 'Invalid data provided.';
    }
}

// ── SEARCH LOGIC ──────────────────────────────
$search = trim($_GET['search'] ?? '');
$whereCust = "WHERE 1=1";
$whereStaff = "WHERE 1=1";

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $whereCust  .= " AND (fullname LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%')";
    $whereStaff .= " AND (fullname LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%')";
}

// LOAD BOTH TABLES
$normalUsers = mysqli_fetch_all(mysqli_query($conn, "SELECT customer_id AS id, fullname, email, phone, created_at, 'user' AS role FROM customer $whereCust ORDER BY fullname"), MYSQLI_ASSOC);
$adminUsers  = mysqli_fetch_all(mysqli_query($conn, "SELECT staff_id AS id, fullname, email, phone, created_at, 'admin' AS role FROM staff $whereStaff ORDER BY fullname"), MYSQLI_ASSOC);
$users = array_merge($adminUsers, $normalUsers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Zone — CoWork Admin</title>
<link rel="stylesheet" href="style.css">
<style>
.modal-bg {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.5); z-index:999;
    align-items:center; justify-content:center;
}
.modal-bg.open { display:flex; }
.modal-box {
    background:#fff; border-radius:var(--radius-lg);
    padding:32px; width:100%; max-width:440px;
    box-shadow:var(--shadow-lg); margin:20px;
}
.action-bar { display:flex; gap:12px; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
</style>
</head>
<body>
<div class="admin-wrapper">
  <?php include 'admin_sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h4>Manage Zone</h4>
      <div class="user-chip"><?= $adminName ?>
        <span class="badge badge-admin" style="margin-left:6px">Admin</span>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <div class="stat-grid" style="margin-bottom:24px">
        <?php
          $totalAdmins = count(array_filter($users, fn($u) => $u['role']==='admin'));
          $totalUsers  = count(array_filter($users, fn($u) => $u['role']==='user'));
        ?>
        <div class="stat-card">
          <div class="stat-label">Total Users</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value"><?= count($users) ?></div>
            <div class="stat-icon stat-icon-olive">👥</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Staff / Admins</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value"><?= $totalAdmins ?></div>
            <div class="stat-icon stat-icon-brown">🔑</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Customers</div>
          <div style="display:flex;justify-content:space-between;align-items:flex-end">
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-icon stat-icon-blue">🙍</div>
          </div>
        </div>
      </div>

      <div class="action-bar">
        <form method="GET" style="display:flex;gap:8px;flex:1;max-width:400px;">
          <input type="text" name="search" placeholder="Search user by name, email, phone..." 
                 value="<?= htmlspecialchars($search) ?>"
                 style="flex:1;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:0.88rem;outline:none">
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
          <?php if($search): ?>
            <a href="manage_zone.php" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
        </form>
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">+ Add Customer</button>
      </div>

      <div class="admin-table-wrap">
        <h3 style="margin-bottom:16px">All Users (<?= count($users) ?>)</h3>
        <table>
          <thead>
            <tr>
              <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
              <th>Role</th><th>Joined</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u):
              $isAdmin = $u['role'] === 'admin';
              $isSelf  = $isAdmin && ((int)$u['id'] === $selfId);
            ?>
            <tr>
              <td style="color:var(--text-light);font-size:0.8rem"><?= $i + 1 ?></td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($u['fullname']) ?></div>
                <?php if ($isSelf): ?>
                  <small style="color:var(--brown);font-size:0.72rem">(you)</small>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone']) ?></td>
              <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:0.82rem;color:var(--text-muted)">
                <?= date('d M Y', strtotime($u['created_at'])) ?>
              </td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <button class="btn btn-sm btn-outline"
                    onclick="openEdit(
                      <?= $u['id'] ?>,
                      '<?= addslashes(htmlspecialchars($u['fullname'])) ?>',
                      '<?= addslashes(htmlspecialchars($u['email'])) ?>',
                      '<?= htmlspecialchars($u['phone']) ?>',
                      '<?= $u['role'] ?>'
                    )">Edit</button>

                  <?php if (!$isSelf): ?>
                    <?php if (!$isAdmin): ?>
                      <a href="manage_zone.php?promote=<?= $u['id'] ?>"
                         class="btn btn-sm btn-primary"
                         onclick="return confirm('Promote <?= addslashes($u['fullname']) ?> to Staff/Admin?')">
                         ⬆ Promote
                      </a>
                    <?php else: ?>
                      <a href="manage_zone.php?demote=<?= $u['id'] ?>"
                         class="btn btn-sm btn-olive"
                         onclick="return confirm('Demote <?= addslashes($u['fullname']) ?> to Customer?')">
                         ⬇ Demote
                      </a>
                    <?php endif; ?>

                    <a href="manage_zone.php?delete=<?= $u['id'] ?>&type=<?= $u['role'] ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete <?= addslashes($u['fullname']) ?>?')">
                       Delete
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal-bg" id="addModal">
  <div class="modal-box">
    <h3 style="margin-bottom:16px">➕ Add New Customer</h3>
    <form method="POST">
      <input type="hidden" name="add_customer" value="1">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="fullname" required placeholder="John Doe">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" required placeholder="john@example.com">
      </div>
      <div class="form-group">
        <label>Phone *</label>
        <input type="tel" name="phone" maxlength="11" required placeholder="0123456789">
        <span class="form-hint">10–11 digits, numbers only</span>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-primary" style="flex:1">Create Customer</button>
        <button type="button" class="btn btn-outline" onclick="closeAddModal()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-bg" id="editModal">
  <div class="modal-box">
    <h3 style="margin-bottom:6px">✏️ Edit User</h3>
    <p id="eRoleBadge" style="margin-bottom:18px;font-size:0.82rem"></p>
    <form method="POST" novalidate>
      <input type="hidden" name="edit_user_id" id="eUID">
      <input type="hidden" name="edit_type"    id="eType">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="fullname" id="eFN" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" id="eEM" required>
      </div>
      <div class="form-group">
        <label>Phone *</label>
        <input type="tel" name="phone" id="ePH" maxlength="11" required>
        <span class="form-hint">10–11 digits, no spaces</span>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeEdit()" style="flex:1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() { document.getElementById('addModal').classList.add('open'); }
function closeAddModal() { document.getElementById('addModal').classList.remove('open'); }

function openEdit(id, fn, em, ph, role) {
  document.getElementById('eUID').value   = id;
  document.getElementById('eType').value  = role;
  document.getElementById('eFN').value    = fn;
  document.getElementById('eEM').value    = em;
  document.getElementById('ePH').value    = ph;
  document.getElementById('eRoleBadge').innerHTML =
    'Editing: <span class="badge badge-' + role + '">' + role.charAt(0).toUpperCase() + role.slice(1) + '</span>';
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }

// Click overlay to close modals
window.addEventListener('click', function(e) {
  if (e.target.id === 'editModal') closeEdit();
  if (e.target.id === 'addModal') closeAddModal();
});
</script>
</body>
</html>