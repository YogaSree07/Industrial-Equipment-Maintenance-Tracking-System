<?php
// ====================================================================
// Technician Profile, Assigned Equipment & Workload History
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid technician ID.', 'error');
    redirect('technicians/index.php');
}

// 1. Fetch Technician Basic Information
$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$technician = $stmt->fetch();

if (!$technician) {
    flash_message('error', 'Technician record not found.', 'error');
    redirect('technicians/index.php');
}

$pageTitle = $technician['name'] . ' (' . $technician['employee_id'] . ')';
$currentPage = 'technicians';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Technicians' => 'technicians/index.php',
    $technician['name'] => ''
];

// 2. Assigned Machines
$machStmt = $pdo->prepare("SELECT * FROM machines WHERE technician_id = :id ORDER BY machine_name ASC");
$machStmt->execute([':id' => $id]);
$assignedMachines = $machStmt->fetchAll();

// 3. Assigned Maintenance Orders
$maintStmt = $pdo->prepare("
    SELECT mr.*, m.machine_name 
    FROM maintenance_records mr 
    JOIN machines m ON mr.machine_id = m.id 
    WHERE mr.technician_id = :id 
    ORDER BY mr.scheduled_date DESC, mr.id DESC
");
$maintStmt->execute([':id' => $id]);
$assignedMaintenance = $maintStmt->fetchAll();

// 4. Assigned Breakdown Tickets
$breakdownStmt = $pdo->prepare("
    SELECT b.*, m.machine_name 
    FROM breakdowns b 
    JOIN machines m ON b.machine_id = m.id 
    WHERE b.technician_id = :id 
    ORDER BY b.reported_date DESC
");
$breakdownStmt->execute([':id' => $id]);
$assignedBreakdowns = $breakdownStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                <?= htmlspecialchars($technician['name']) ?>
            </h1>
            <code><?= htmlspecialchars($technician['employee_id']) ?></code>
            <?= get_status_badge($technician['status']) ?>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
            <?= htmlspecialchars($technician['department']) ?> Department &bull; Specialization: <strong><?= htmlspecialchars($technician['specialization']) ?></strong>
        </p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <a href="<?= url('technicians/edit.php?id=' . $technician['id']) ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
        </a>
        <a href="<?= url('technicians/delete.php?id=' . $technician['id']) ?>" 
           class="btn btn-outline-danger btn-sm"
           data-confirm-delete
           data-item-name="<?= htmlspecialchars($technician['name']) ?>">
            <i class="fa-solid fa-trash-can"></i> Remove
        </a>
        <a href="<?= url('technicians/index.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Directory
        </a>
    </div>
</div>

<!-- Basic Information Overview Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-address-card"></i> Personnel Information & Contact</h2>
        <span class="badge badge-secondary"><?= count($assignedMachines) ?> Assigned Machines</span>
    </div>
    <div class="card-body">
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Employee ID Badge</span>
                <span class="detail-value"><code><?= htmlspecialchars($technician['employee_id']) ?></code></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Department</span>
                <span class="detail-value"><?= htmlspecialchars($technician['department']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Direct Phone</span>
                <span class="detail-value">
                    <i class="fa-solid fa-phone" style="color: var(--primary);"></i>
                    <a href="tel:<?= htmlspecialchars($technician['phone']) ?>"><?= htmlspecialchars($technician['phone']) ?></a>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Work Email</span>
                <span class="detail-value">
                    <i class="fa-solid fa-envelope" style="color: var(--primary);"></i>
                    <a href="mailto:<?= htmlspecialchars($technician['email']) ?>"><?= htmlspecialchars($technician['email']) ?></a>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Date of Joining</span>
                <span class="detail-value"><?= format_date($technician['joining_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Specialist Domain</span>
                <span class="detail-value"><?= htmlspecialchars($technician['specialization']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Section 1: Assigned Industrial Machines -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-industry"></i> Machines Assigned as Lead Caretaker</h2>
            <div class="card-subtitle">Equipment where this technician is primary responsible engineer</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Machine Name</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedMachines)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fa-solid fa-industry"></i>
                            <h3>No machines assigned</h3>
                            <p>This technician is not currently assigned as lead engineer for any equipment.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignedMachines as $am): ?>
                        <tr>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $am['id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($am['machine_name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);"><?= htmlspecialchars($am['model_number']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($am['machine_type']) ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($am['department']) ?></span></td>
                            <td><i class="fa-solid fa-location-dot" style="color: var(--danger);"></i> <?= htmlspecialchars($am['location']) ?></td>
                            <td><?= get_status_badge($am['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('machines/view.php?id=' . $am['id']) ?>" class="btn-icon" title="View Machine">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section 2: Assigned Maintenance Records -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Servicing & Maintenance Workload</h2>
            <div class="card-subtitle">Maintenance tasks performed or scheduled for this technician</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Equipment</th>
                    <th>Category</th>
                    <th>Scheduled Date</th>
                    <th>Completed Date</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedMaintenance)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <h3>No maintenance records assigned</h3>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignedMaintenance as $m): ?>
                        <tr>
                            <td><strong>#<?= $m['id'] ?></strong></td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $m['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($m['machine_name']) ?>
                                </a>
                            </td>
                            <td><?= get_maintenance_type_badge($m['maintenance_type']) ?></td>
                            <td><?= format_date($m['scheduled_date']) ?></td>
                            <td><?= format_date($m['completed_date']) ?></td>
                            <td><strong><?= format_currency($m['cost']) ?></strong></td>
                            <td><?= get_status_badge($m['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('maintenance/view.php?id=' . $m['id']) ?>" class="btn-icon" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section 3: Assigned Breakdowns -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Breakdown Tickets Handled</h2>
            <div class="card-subtitle">Emergency repair tasks dispatched to this technician</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Reported Date</th>
                    <th>Machine</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedBreakdowns)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
                            <h3>No breakdown tickets dispatched</h3>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignedBreakdowns as $b): ?>
                        <tr>
                            <td><strong>#<?= $b['id'] ?></strong></td>
                            <td><?= format_datetime($b['reported_date']) ?></td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $b['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($b['machine_name']) ?>
                                </a>
                            </td>
                            <td><?= get_severity_badge($b['severity']) ?></td>
                            <td><?= get_status_badge($b['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('breakdowns/view.php?id=' . $b['id']) ?>" class="btn-icon" title="View Ticket">
                                    <i class="fa-solid fa-eye"></i>
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
