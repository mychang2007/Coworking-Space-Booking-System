<?php
// --- ADD THIS LINE TO FIX THE TIMEZONE ---
date_default_timezone_set('Asia/Kuala_Lumpur');

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        $back = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?redirect=$back"); exit;
    }
}

function requireAdmin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: admin_login.php"); exit;
    }
}

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool    { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function isUser(): bool     { return ($_SESSION['user_role'] ?? '') === 'user'; }
function isSuperAdmin(): bool {
    return isAdmin() && ($_SESSION['admin_subrole'] ?? '') === 'superadmin';
}
