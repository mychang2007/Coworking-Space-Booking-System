<?php
/**
 * auth.php — Session helpers for split normal_users / admins tables.
 *
 * Session variables set on login:
 *   $_SESSION['user_id']    — row id
 *   $_SESSION['user_name']  — fullname
 *   $_SESSION['user_role']  — 'user' | 'admin'
 *   $_SESSION['user_email'] — email
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/** Redirect to login if not logged in (any role). */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?redirect=$redirect");
        exit;
    }
}

/** Redirect to home.php if not an admin. */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: home.php");
        exit;
    }
}

/** Redirect to admin_dashboard.php if not a normal user. */
function requireUser(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'user') {
        header("Location: admin_dashboard.php");
        exit;
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function isUser(): bool {
    return ($_SESSION['user_role'] ?? '') === 'user';
}