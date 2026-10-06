<?php
// ====================================================================
// Application Entry Point / Router
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
