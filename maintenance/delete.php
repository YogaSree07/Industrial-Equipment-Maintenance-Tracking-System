<?php
// ====================================================================
// Delete Maintenance Record Handler
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid maintenance ID.', 'error');
    redirect('maintenance/index.php');
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM maintenance_records WHERE id = :id");
    $stmt->execute([':id' => $id]);

    flash_message('success', "Maintenance log #{$id} has been permanently deleted.");
} catch (Exception $e) {
    flash_message('error', 'Failed to delete maintenance log: ' . $e->getMessage(), 'error');
}

redirect('maintenance/index.php');
