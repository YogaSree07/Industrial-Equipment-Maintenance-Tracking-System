<?php
// ====================================================================
// View Maintenance Work Order Details
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid maintenance record ID.', 'error');
    redirect('maintenance/index.php');
}

$stmt = $pdo->prepare("
    SELECT mr.*, 
           m.id as machine_id, m.machine_name, m.model_number, m.manufacturer, m.department, m.location, m.status as machine_status,
           t.id as technician_id, t.name as technician_name, t.employee_id as technician_code, t.phone as technician_phone, t.email as technician_email, t.specialization as technician_spec
    FROM maintenance_records mr
    JOIN machines m ON mr.machine_id = m.id
    LEFT JOIN technicians t ON mr.technician_id = t.id
    WHERE mr.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$record = $stmt->fetch();

if (!$record) {
    flash_message('error', 'Maintenance record not found.', 'error');
    redirect('maintenance/index.php');
}

$pageTitle = 'Maintenance Work Order #' . $id;
$currentPage = 'maintenance';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Maintenance' => 'maintenance/index.php',
    'Order #' . $id => ''
];

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                Maintenance Order #<?= $record['id'] ?>
            </h1>
            <?= get_status_badge($record['status']) ?>
            <?= get_maintenance_type_badge($record['maintenance_type']) ?>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
            Target Equipment: <strong><a href="<?= url('machines/view.php?id=' . $record['machine_id']) ?>"><?= htmlspecialchars($record['machine_name']) ?></a></strong> &bull; Scheduled: <?= format_date($record['scheduled_date']) ?>
        </p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <a href="<?= url('maintenance/edit.php?id=' . $record['id']) ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> Edit / Update Status
        </a>
        <a href="<?= url('maintenance/delete.php?id=' . $record['id']) ?>" 
           class="btn btn-outline-danger btn-sm"
           data-confirm-delete
           data-item-name="Maintenance Order #<?= $record['id'] ?>">
            <i class="fa-solid fa-trash-can"></i> Delete
        </a>
        <a href="<?= url('maintenance/index.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> All Logs
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-file-invoice"></i> Work Order Specifications & Summary</h2>
        <span class="badge badge-secondary"><?= htmlspecialchars($record['maintenance_type']) ?> Maintenance</span>
    </div>
    <div class="card-body">
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Serviced Machine</span>
                <span class="detail-value">
                    <a href="<?= url('machines/view.php?id=' . $record['machine_id']) ?>" style="font-size: 15px;">
                        <?= htmlspecialchars($record['machine_name']) ?>
                    </a>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        <?= htmlspecialchars($record['manufacturer']) ?> &bull; <code><?= htmlspecialchars($record['model_number']) ?></code>
                    </div>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Department / Location</span>
                <span class="detail-value">
                    <?= htmlspecialchars($record['department']) ?>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        <i class="fa-solid fa-location-dot" style="color: var(--danger);"></i> <?= htmlspecialchars($record['location']) ?>
                    </div>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Scheduled Date</span>
                <span class="detail-value"><?= format_date($record['scheduled_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Completion Date</span>
                <span class="detail-value"><?= format_date($record['completed_date'], 'Pending Completion') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Assigned Technician</span>
                <span class="detail-value">
                    <?php if (!empty($record['technician_name'])): ?>
                        <a href="<?= url('technicians/view.php?id=' . $record['technician_id']) ?>">
                            <i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($record['technician_name']) ?>
                        </a>
                        <div style="font-size: 11.5px; color: var(--text-muted);">
                            <?= htmlspecialchars($record['technician_spec']) ?> &bull; <?= htmlspecialchars($record['technician_phone']) ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Total Recorded Cost</span>
                <span class="detail-value" style="color: var(--primary); font-size: 18px;">
                    <?= format_currency($record['cost']) ?>
                </span>
            </div>
        </div>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
            <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">
                Description & Work Completed
            </h4>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; font-size: 14px; line-height: 1.6;">
                <?= nl2br(htmlspecialchars($record['description'])) ?>
            </div>
        </div>

        <div style="margin-top: 18px;">
            <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">
                Parts & Consumables Replaced
            </h4>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; font-size: 13.5px;">
                <?php if (!empty($record['parts_replaced'])): ?>
                    <i class="fa-solid fa-cube" style="color: var(--primary); margin-right: 6px;"></i>
                    <?= htmlspecialchars($record['parts_replaced']) ?>
                <?php else: ?>
                    <span class="text-muted">No replacement parts required for this maintenance operation.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
