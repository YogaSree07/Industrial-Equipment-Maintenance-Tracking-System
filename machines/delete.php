<?php
// ====================================================================
// Delete Machine Handler
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid machine ID.', 'error');
    redirect('machines/index.php');
}

try {
    $pdo = getDBConnection();
    // Fetch machine name first for user-friendly flash message
    $stmt = $pdo->prepare("SELECT machine_name FROM machines WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $machine = $stmt->fetch();

    if ($machine) {
        $delStmt = $pdo->prepare("DELETE FROM machines WHERE id = :id");
        $delStmt->execute([':id' => $id]);
        flash_message('success', "Machine '{$machine['machine_name']}' and its associated logs have been safely deleted.");
    } else {
        flash_message('warning', 'Equipment record was not found or already deleted.', 'warning');
    }
} catch (Exception $e) {
    flash_message('error', 'Unable to delete machine: ' . $e->getMessage(), 'error');
}

redirect('machines/index.php');
