<?php
// ====================================================================
// Breakdown Incident Reports & Downtime Tracking
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Breakdown Incident Reports';
$currentPage = 'breakdowns';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Breakdowns' => ''];

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$severityFilter = trim($_GET['severity'] ?? '');
$machineFilter = (int)($_GET['machine_id'] ?? 0);

// Machines list for filter
$machinesList = $pdo->query("SELECT id, machine_name FROM machines ORDER BY machine_name ASC")->fetchAll();

$query = "
    SELECT b.*,
           m.machine_name, m.department, m.location, m.model_number,
           t.name as technician_name, t.employee_id as technician_code
    FROM breakdowns b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN technicians t ON b.technician_id = t.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (m.machine_name LIKE :search OR b.problem_description LIKE :search OR b.resolution_notes LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $query .= " AND b.status = :status";
    $params[':status'] = $statusFilter;
}

if ($severityFilter !== '') {
    $query .= " AND b.severity = :severity";
    $params[':severity'] = $severityFilter;
}

if ($machineFilter > 0) {
    $query .= " AND b.machine_id = :machine_id";
    $params[':machine_id'] = $machineFilter;
}

$query .= " ORDER BY (b.status != 'Resolved') DESC, b.reported_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$breakdowns = $stmt->fetchAll();

$statuses = ['Reported', 'In Progress', 'Resolved'];
$severities = ['Critical', 'High', 'Medium', 'Low'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter & Action Controls -->
<div class="filter-bar">
    <form action="<?= url('breakdowns/index.php') ?>" method="GET" class="filter-group" style="flex: 1;">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search problem description, notes..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= ($statusFilter === $st) ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>

        <select name="severity" class="form-select" style="width: auto;">
            <option value="">All Severities</option>
            <?php foreach ($severities as $sv): ?>
                <option value="<?= $sv ?>" <?= ($severityFilter === $sv) ? 'selected' : '' ?>><?= $sv ?></option>
            <?php endforeach; ?>
        </select>

        <select name="machine_id" class="form-select" style="width: auto;">
            <option value="0">All Equipment</option>
            <?php foreach ($machinesList as $mac): ?>
                <option value="<?= $mac['id'] ?>" <?= ($machineFilter === (int)$mac['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mac['machine_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> Filter
        </button>

        <?php if ($search !== '' || $statusFilter !== '' || $severityFilter !== '' || $machineFilter > 0): ?>
            <a href="<?= url('breakdowns/index.php') ?>" class="btn btn-secondary btn-sm" title="Clear Filters">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <div>
        <a href="<?= url('breakdowns/add.php') ?>" class="btn btn-danger">
            <i class="fa-solid fa-bullhorn"></i> Report Machine Breakdown
        </a>
    </div>
</div>

<!-- Breakdowns List Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Machinery Breakdown Incident Logs</h2>
            <div class="card-subtitle">Showing <?= count($breakdowns) ?> breakdown ticket(s) recorded in the factory</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Reported On</th>
                    <th>Equipment Affected</th>
                    <th>Severity</th>
                    <th>Problem Description</th>
                    <th>Assigned Technician</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($breakdowns)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fa-solid fa-shield-halved" style="color: var(--success);"></i>
                            <h3>No breakdown records reported</h3>
                            <p>No active or historical breakdowns match your filter parameters.</p>
                            <a href="<?= url('breakdowns/add.php') ?>" class="btn btn-danger btn-sm">Report Breakdown Incident</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($breakdowns as $b): 
                        $isCriticalActive = ($b['severity'] === 'Critical' && $b['status'] !== 'Resolved');
                    ?>
                        <tr class="<?= $isCriticalActive ? 'overdue-row' : '' ?>">
                            <td><strong>#<?= $b['id'] ?></strong></td>
                            <td><?= format_datetime($b['reported_date']) ?></td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $b['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($b['machine_name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                    <?= htmlspecialchars($b['department']) ?> &bull; <?= htmlspecialchars($b['location']) ?>
                                </div>
                            </td>
                            <td><?= get_severity_badge($b['severity']) ?></td>
                            <td style="max-width: 280px;">
                                <div style="font-weight: 500; font-size: 13px;">
                                    <?= htmlspecialchars(substr($b['problem_description'], 0, 85)) . (strlen($b['problem_description']) > 85 ? '...' : '') ?>
                                </div>
                            </td>
                            <td>
                                <?= !empty($b['technician_name']) ? htmlspecialchars($b['technician_name']) : '<span class="text-muted">Unassigned</span>' ?>
                            </td>
                            <td><?= get_status_badge($b['status']) ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= url('breakdowns/view.php?id=' . $b['id']) ?>" class="btn-icon" title="View Incident Ticket">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('breakdowns/edit.php?id=' . $b['id']) ?>" class="btn-icon" title="Update / Resolve Breakdown">
                                        <i class="fa-solid fa-wrench"></i>
                                    </a>
                                    <a href="<?= url('breakdowns/delete.php?id=' . $b['id']) ?>" 
                                       class="btn-icon delete" 
                                       title="Delete Breakdown Log"
                                       data-confirm-delete
                                       data-item-name="Breakdown Ticket #<?= $b['id'] ?>">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
