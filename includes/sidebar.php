<?php
// ====================================================================
// Sidebar Component
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$pdo = getDBConnection();
$sidebarCounters = get_sidebar_counters($pdo);
$user = current_user();
?>
<!-- Navigation Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand / Logo Area -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-gears"></i>
        </div>
        <div class="brand-info">
            <span class="brand-title">EquipTrack Pro</span>
            <span class="brand-subtitle">Industrial Suite</span>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu-wrapper">
        <div class="sidebar-section-label">Operations</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= url('dashboard.php') ?>" class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= url('machines/index.php') ?>" class="nav-link <?= ($currentPage === 'machines') ? 'active' : '' ?>">
                    <i class="fa-solid fa-industry nav-icon"></i>
                    <span>Machines</span>
                </a>
            </li>
            <li>
                <a href="<?= url('maintenance/index.php') ?>" class="nav-link <?= ($currentPage === 'maintenance') ? 'active' : '' ?>">
                    <i class="fa-solid fa-screwdriver-wrench nav-icon"></i>
                    <span>Maintenance</span>
                </a>
            </li>
            <li>
                <a href="<?= url('breakdowns/index.php') ?>" class="nav-link <?= ($currentPage === 'breakdowns') ? 'active' : '' ?>">
                    <i class="fa-solid fa-triangle-exclamation nav-icon"></i>
                    <span>Breakdowns</span>
                    <?php if ($sidebarCounters['breakdowns'] > 0): ?>
                        <span class="nav-badge danger" title="<?= $sidebarCounters['breakdowns'] ?> Active Breakdown(s)"><?= $sidebarCounters['breakdowns'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= url('technicians/index.php') ?>" class="nav-link <?= ($currentPage === 'technicians') ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear nav-icon"></i>
                    <span>Technicians</span>
                </a>
            </li>
            <li>
                <a href="<?= url('schedule/index.php') ?>" class="nav-link <?= ($currentPage === 'schedule') ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-days nav-icon"></i>
                    <span>Maintenance Schedule</span>
                    <?php if ($sidebarCounters['overdue'] > 0): ?>
                        <span class="nav-badge warning" title="<?= $sidebarCounters['overdue'] ?> Overdue Tasks"><?= $sidebarCounters['overdue'] ?> Overdue</span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Analytics & Account</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= url('reports/index.php') ?>" class="nav-link <?= ($currentPage === 'reports') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie nav-icon"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="<?= url('profile/index.php') ?>" class="nav-link <?= ($currentPage === 'profile') ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-shield nav-icon"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="<?= url('logout.php') ?>" class="nav-link">
                    <i class="fa-solid fa-arrow-right-from-bracket nav-icon"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Footer / Logged-in Operator details -->
    <div class="sidebar-footer">
        <div class="user-snippet">
            <div class="user-avatar-sm">
                <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-snippet-details">
                <span class="user-snippet-name"><?= htmlspecialchars($user['name'] ?? 'Administrator') ?></span>
                <span class="user-snippet-role"><?= htmlspecialchars($user['role'] ?? 'Plant Manager') ?></span>
            </div>
        </div>
        <a href="<?= url('logout.php') ?>" class="logout-icon-btn" title="Sign Out">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>
</aside>
