<?php
// ====================================================================
// Header Component (HTML Head, Assets, Topbar & Navigation)
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Enforce login on all pages including header by default
require_login();

$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?> - Industrial Maintenance</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Chart.js for Industrial Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Custom Industrial Dashboard Stylesheet -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Component -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Sticky Topbar Header -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-title-box">
                    <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                    <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
                        <div class="breadcrumb-trail">
                            <a href="<?= url('dashboard.php') ?>">Home</a>
                            <?php foreach ($breadcrumbs as $crumbLabel => $crumbUrl): ?>
                                <span class="breadcrumb-separator">/</span>
                                <?php if (!empty($crumbUrl)): ?>
                                    <a href="<?= url($crumbUrl) ?>"><?= htmlspecialchars($crumbLabel) ?></a>
                                <?php else: ?>
                                    <span><?= htmlspecialchars($crumbLabel) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="topbar-right">
                <div class="system-status-indicator" title="Live Database Status">
                    <span class="status-dot"></span>
                    <span>Factory Floor Live</span>
                </div>

                <a href="<?= url('profile/index.php') ?>" class="btn btn-secondary btn-sm" title="My Profile">
                    <i class="fa-solid fa-user-circle"></i>
                    <span><?= htmlspecialchars($user['name'] ?? 'Admin') ?></span>
                </a>

                <a href="<?= url('logout.php') ?>" class="btn btn-outline-danger btn-sm" title="Logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <main class="page-container">
            <!-- Flash Message Alerts -->
            <?= flash_message('success') ?>
            <?= flash_message('error') ?>
            <?= flash_message('warning') ?>
            <?= flash_message('info') ?>
            <?= flash_message('flash') ?>
