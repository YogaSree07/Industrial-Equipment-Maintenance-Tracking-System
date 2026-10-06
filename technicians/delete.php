<?php
// ====================================================================
// Delete Technician Record Handler
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid technician ID.', 'error');
    redirect('technicians/index.php');
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT name FROM technicians WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $tech = $stmt->fetch();

    if ($tech) {
        $del = $pdo->prepare("DELETE FROM technicians WHERE id = :id");
        $del->execute([':id' => $id]);
        flash_message('success', "Technician '{$tech['name']}' was removed. Associated equipment assignments have been reset.");
    } else {
        flash_message('warning', 'Technician record was not found or already deleted.', 'warning');
    }
} catch (Exception $e) {
    flash_message('error', 'Failed to delete technician: ' . $e->getMessage(), 'error');
}

redirect('technicians/index.php');
