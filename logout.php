<?php
session_start();
$role = $_SESSION['user_role'] ?? 'user';
session_unset();
session_destroy();
header("Location: " . ($role === 'admin' ? 'admin_login.php?logout=1' : 'login.php?logout=1'));
exit;