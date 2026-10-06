<?php
// ====================================================================
// Machine Details Profile, Maintenance & Breakdown History
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid machine ID requested.', 'error');
    redirect('machines/index.php');
}

// 1. Fetch Machine Details with Assigned Technician Info
$stmt = $pdo->prepare("
    SELECT m.*, 
           t.name as technician_name, 
           t.employee_id as technician_code, 
           t.phone as technician_phone, 
           t.email as technician_email, 
           t.specialization as technician_spec
    FROM machines m
    LEFT JOIN technicians t ON m.technician_id = t.id
    WHERE m.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$machine = $stmt->fetch();

if (!$machine) {
    flash_message('error', 'Machine not found in database.', 'error');
    redirect('machines/index.php');
}

$pageTitle = $machine['machine_name'];
$currentPage = 'machines';
$breadcrumbs = [
    'Dashboard' => 'dashboard.php',
    'Machines'  => 'machines/index.php',
    $machine['machine_name'] => ''
];

// 2. Fetch Maintenance History for this specific machine
$maintStmt = $pdo->prepare("
    SELECT mr.*, t.name as technician_name
    FROM maintenance_records mr
    LEFT JOIN technicians t ON mr.technician_id = t.id
    WHERE mr.machine_id = :id
    ORDER BY mr.scheduled_date DESC, mr.id DESC
");
$maintStmt->execute([':id' => $id]);
$maintenanceHistory = $maintStmt->fetchAll();

// 3. Fetch Breakdown History for this specific machine
$breakdownStmt = $pdo->prepare("
    SELECT b.*, t.name as technician_name
    FROM breakdowns b
    LEFT JOIN technicians t ON b.technician_id = t.id
    WHERE b.machine_id = :id
    ORDER BY b.reported_date DESC
");
$breakdownStmt->execute([':id' => $id]);
$breakdownHistory = $breakdownStmt->fetchAll();

// Calculate total maintenance expenditure on this equipment
$totalMaintCost = 0;
foreach ($maintenanceHistory as $mh) {
    $totalMaintCost += (float)$mh['cost'];
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Equipment Header & Action Controls -->
<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                <?= htmlspecialchars($machine['machine_name']) ?>
            </h1>
            <?= get_status_badge($machine['status']) ?>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
            Asset ID: <strong>#<?= $machine['id'] ?></strong> &bull; Model: <code><?= htmlspecialchars($machine['model_number']) ?></code> &bull; <?= htmlspecialchars($machine['manufacturer']) ?>
        </p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <a href="<?= url('maintenance/add.php?machine_id=' . $machine['id']) ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-screwdriver-wrench"></i> Schedule Maintenance
        </a>
        <a href="<?= url('breakdowns/add.php?machine_id=' . $machine['id']) ?>" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-bullhorn"></i> Report Breakdown
        </a>
        <a href="<?= url('machines/edit.php?id=' . $machine['id']) ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> Edit Equipment
        </a>
        <a href="<?= url('machines/delete.php?id=' . $machine['id']) ?>" 
           class="btn btn-outline-danger btn-sm"
           data-confirm-delete
           data-item-name="<?= htmlspecialchars($machine['machine_name']) ?>">
            <i class="fa-solid fa-trash-can"></i>
        </a>
    </div>
</div>

<!-- Machine Specifications Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Machine Specifications & Location</h2>
        <span class="badge badge-secondary"><?= htmlspecialchars($machine['department']) ?> Dept</span>
    </div>
    <div class="card-body">
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Equipment Category</span>
                <span class="detail-value"><?= htmlspecialchars($machine['machine_type']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Department</span>
                <span class="detail-value"><?= htmlspecialchars($machine['department']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Floor Location</span>
                <span class="detail-value">
                    <i class="fa-solid fa-location-dot" style="color: var(--danger); margin-right: 4px;"></i>
                    <?= htmlspecialchars($machine['location']) ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Manufacturer (OEM)</span>
                <span class="detail-value"><?= htmlspecialchars($machine['manufacturer']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Model / Serial Number</span>
                <span class="detail-value"><code><?= htmlspecialchars($machine['model_number']) ?></code></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Commissioning Date</span>
                <span class="detail-value"><?= format_date($machine['installation_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Last Maintenance</span>
                <span class="detail-value"><?= format_date($machine['last_maintenance_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Next Maintenance Due</span>
                <span class="detail-value"><?= format_date($machine['next_maintenance_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Assigned Lead Technician</span>
                <span class="detail-value">
                    <?php if (!empty($machine['technician_name'])): ?>
                        <a href="<?= url('technicians/view.php?id=' . $machine['technician_id']) ?>">
                            <i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($machine['technician_name']) ?>
                        </a>
                        <span style="font-size: 11.5px; color: var(--text-muted); display: block;">
                            <?= htmlspecialchars($machine['technician_spec']) ?> (<?= htmlspecialchars($machine['technician_phone']) ?>)
                        </span>
                    <?php else: ?>
                        <span class="text-muted" style="font-style: italic;">Unassigned</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Total Lifecycle Maintenance Cost</span>
                <span class="detail-value" style="color: var(--primary); font-size: 16px;">
                    <?= format_currency($totalMaintCost) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance History for this Machine -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Maintenance History</h2>
            <div class="card-subtitle">All scheduled, routine and corrective servicing logged for this equipment</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('maintenance/add.php?machine_id=' . $machine['id']) ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> New Service Log
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Type</th>
                    <th>Scheduled Date</th>
                    <th>Completed Date</th>
                    <th>Technician</th>
                    <th>Parts Replaced</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($maintenanceHistory)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-wrench"></i>
                            <h3>No maintenance history recorded</h3>
                            <p>No servicing events have been logged for this machine yet.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($maintenanceHistory as $mh): ?>
                        <tr>
                            <td><strong>#<?= $mh['id'] ?></strong></td>
                            <td><?= get_maintenance_type_badge($mh['maintenance_type']) ?></td>
                            <td><?= format_date($mh['scheduled_date']) ?></td>
                            <td><?= format_date($mh['completed_date']) ?></td>
                            <td><?= htmlspecialchars($mh['technician_name'] ?? 'Unassigned') ?></td>
                            <td><?= !empty($mh['parts_replaced']) ? htmlspecialchars($mh['parts_replaced']) : '<span class="text-muted">None</span>' ?></td>
                            <td><strong><?= format_currency($mh['cost']) ?></strong></td>
                            <td><?= get_status_badge($mh['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('maintenance/view.php?id=' . $mh['id']) ?>" class="btn-icon" title="View Record">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= url('maintenance/edit.php?id=' . $mh['id']) ?>" class="btn-icon" title="Edit Record">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Breakdown Incident History for this Machine -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Breakdown Incident History</h2>
            <div class="card-subtitle">Historical machine breakdown tickets and emergency downtime logs</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('breakdowns/add.php?machine_id=' . $machine['id']) ?>" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-bullhorn"></i> Log Breakdown
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Reported Date</th>
                    <th>Problem Description</th>
                    <th>Severity</th>
                    <th>Assigned Tech</th>
                    <th>Resolution Time</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($breakdownHistory)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
                            <h3>No breakdown records reported</h3>
                            <p>This equipment has had zero recorded breakdowns or disruptions.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($breakdownHistory as $bh): ?>
                        <tr>
                            <td><strong>#<?= $bh['id'] ?></strong></td>
                            <td><?= format_datetime($bh['reported_date']) ?></td>
                            <td style="max-width: 260px;">
                                <div style="font-weight: 500;"><?= htmlspecialchars(substr($bh['problem_description'], 0, 90)) . (strlen($bh['problem_description']) > 90 ? '...' : '') ?></div>
                            </td>
                            <td><?= get_severity_badge($bh['severity']) ?></td>
                            <td><?= htmlspecialchars($bh['technician_name'] ?? 'Unassigned') ?></td>
                            <td><?= format_datetime($bh['resolution_time']) ?></td>
                            <td><?= get_status_badge($bh['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('breakdowns/view.php?id=' . $bh['id']) ?>" class="btn-icon" title="View Ticket">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= url('breakdowns/edit.php?id=' . $bh['id']) ?>" class="btn-icon" title="Edit Ticket">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
