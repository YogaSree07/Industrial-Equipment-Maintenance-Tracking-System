<?php
// ====================================================================
// Delete Breakdown Incident Record
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid breakdown incident ID.', 'error');
    redirect('breakdowns/index.php');
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM breakdowns WHERE id = :id");
    $stmt->execute([':id' => $id]);

    flash_message('success', "Breakdown ticket #{$id} has been permanently deleted.");
} catch (Exception $e) {
    flash_message('error', 'Failed to delete breakdown ticket: ' . $e->getMessage(), 'error');
}

redirect('breakdowns/index.php');
