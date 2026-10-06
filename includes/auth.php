<?php
// ====================================================================
// Authentication Middleware & Session Management
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks whether an authenticated session exists
 * 
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user details from session
 * 
 * @return array|null
 */
function current_user() {
    if (is_logged_in()) {
        return $_SESSION['user'] ?? [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role'] ?? 'Staff'
        ];
    }
    return null;
}

/**
 * Enforce that the user must be authenticated to view this page.
 * If not authenticated, redirects to login page.
 * 
 * @return void
 */
function require_login() {
    if (!is_logged_in()) {
        // Save current URI for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('dashboard.php');
        flash_message('login_error', 'Please log in to access the system.', 'error');
        redirect('login.php');
    }
}
