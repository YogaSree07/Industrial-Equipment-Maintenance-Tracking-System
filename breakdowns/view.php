<?php
// ====================================================================
// View Breakdown Incident Details & Downtime Analysis
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid breakdown ID.', 'error');
    redirect('breakdowns/index.php');
}

$stmt = $pdo->prepare("
    SELECT b.*,
           m.id as machine_id, m.machine_name, m.model_number, m.manufacturer, m.department, m.location, m.status as machine_status,
           t.id as technician_id, t.name as technician_name, t.employee_id as technician_code, t.phone as technician_phone, t.email as technician_email, t.specialization as technician_spec
    FROM breakdowns b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN technicians t ON b.technician_id = t.id
    WHERE b.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$breakdown = $stmt->fetch();

if (!$breakdown) {
    flash_message('error', 'Breakdown incident not found.', 'error');
    redirect('breakdowns/index.php');
}

$pageTitle = 'Breakdown Ticket #' . $id;
$currentPage = 'breakdowns';
$breadcrumbs = [
    'Dashboard'  => 'dashboard.php',
    'Breakdowns' => 'breakdowns/index.php',
    'Ticket #' . $id => ''
];

// Calculate downtime duration
$downtimeText = 'Ongoing';
if (!empty($breakdown['resolution_time'])) {
    $start = new DateTime($breakdown['reported_date']);
    $end = new DateTime($breakdown['resolution_time']);
    $diff = $start->diff($end);
    $parts = [];
    if ($diff->d > 0) $parts[] = $diff->d . 'd';
    if ($diff->h > 0) $parts[] = $diff->h . 'h';
    if ($diff->i > 0) $parts[] = $diff->i . 'm';
    $downtimeText = !empty($parts) ? implode(' ', $parts) : '< 1m';
} else {
    $start = new DateTime($breakdown['reported_date']);
    $now = new DateTime();
    $diff = $start->diff($now);
    $parts = [];
    if ($diff->d > 0) $parts[] = $diff->d . 'd';
    if ($diff->h > 0) $parts[] = $diff->h . 'h';
    if ($diff->i > 0) $parts[] = $diff->i . 'm';
    $downtimeText = 'Ongoing (' . implode(' ', $parts) . ')';
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin: 0;">
                Breakdown Incident #<?= $breakdown['id'] ?>
            </h1>
            <?= get_severity_badge($breakdown['severity']) ?>
            <?= get_status_badge($breakdown['status']) ?>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
            Target Machine: <strong><a href="<?= url('machines/view.php?id=' . $breakdown['machine_id']) ?>"><?= htmlspecialchars($breakdown['machine_name']) ?></a></strong> &bull; Reported: <?= format_datetime($breakdown['reported_date']) ?>
        </p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <a href="<?= url('breakdowns/edit.php?id=' . $breakdown['id']) ?>" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-wrench"></i> Update / Resolve Ticket
        </a>
        <a href="<?= url('breakdowns/delete.php?id=' . $breakdown['id']) ?>" 
           class="btn btn-outline-danger btn-sm"
           data-confirm-delete
           data-item-name="Breakdown Ticket #<?= $breakdown['id'] ?>">
            <i class="fa-solid fa-trash-can"></i> Delete
        </a>
        <a href="<?= url('breakdowns/index.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> All Incidents
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-file-circle-exclamation"></i> Breakdown Incident Details & Downtime Metrics</h2>
        <span class="badge badge-secondary"><?= htmlspecialchars($breakdown['department']) ?></span>
    </div>
    <div class="card-body">
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Affected Machine</span>
                <span class="detail-value">
                    <a href="<?= url('machines/view.php?id=' . $breakdown['machine_id']) ?>" style="font-size: 15px;">
                        <?= htmlspecialchars($breakdown['machine_name']) ?>
                    </a>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        <code><?= htmlspecialchars($breakdown['model_number']) ?></code> &bull; <?= htmlspecialchars($breakdown['manufacturer']) ?>
                    </div>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Floor Location</span>
                <span class="detail-value">
                    <i class="fa-solid fa-location-dot" style="color: var(--danger);"></i>
                    <?= htmlspecialchars($breakdown['location']) ?> (<?= htmlspecialchars($breakdown['department']) ?>)
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Incident Reported At</span>
                <span class="detail-value"><?= format_datetime($breakdown['reported_date']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Repair Started At</span>
                <span class="detail-value"><?= format_datetime($breakdown['start_time'], 'Not yet dispatched') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Resolution Timestamp</span>
                <span class="detail-value"><?= format_datetime($breakdown['resolution_time'], 'Unresolved / In Progress') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Total Downtime Duration</span>
                <span class="detail-value" style="color: <?= $breakdown['status'] === 'Resolved' ? 'var(--primary)' : 'var(--danger)' ?>; font-size: 16px;">
                    <i class="fa-solid fa-stopwatch"></i> <?= $downtimeText ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Assigned Technician</span>
                <span class="detail-value">
                    <?php if (!empty($breakdown['technician_name'])): ?>
                        <a href="<?= url('technicians/view.php?id=' . $breakdown['technician_id']) ?>">
                            <i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($breakdown['technician_name']) ?>
                        </a>
                        <div style="font-size: 11.5px; color: var(--text-muted);">
                            <?= htmlspecialchars($breakdown['technician_spec']) ?> &bull; <?= htmlspecialchars($breakdown['technician_phone']) ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Current Machine Status</span>
                <span class="detail-value">
                    <?= get_status_badge($breakdown['machine_status']) ?>
                </span>
            </div>
        </div>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
            <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--danger); margin-bottom: 8px;">
                Problem Description & Symptoms
            </h4>
            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius-md); padding: 16px; font-size: 14px; line-height: 1.6; color: #9f1239;">
                <?= nl2br(htmlspecialchars($breakdown['problem_description'])) ?>
            </div>
        </div>

        <div style="margin-top: 18px;">
            <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">
                Resolution Notes & Corrective Actions
            </h4>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; font-size: 14px; line-height: 1.6;">
                <?php if (!empty($breakdown['resolution_notes'])): ?>
                    <?= nl2br(htmlspecialchars($breakdown['resolution_notes'])) ?>
                <?php else: ?>
                    <span class="text-muted">No resolution notes logged yet. Ongoing diagnosis.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
