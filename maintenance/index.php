<?php
// ====================================================================
// Maintenance Records Management
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Equipment Maintenance Logs';
$currentPage = 'maintenance';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Maintenance' => ''];

// Filter & search parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$machineFilter = (int)($_GET['machine_id'] ?? 0);

// Fetch machines for filter dropdown
$machinesList = $pdo->query("SELECT id, machine_name FROM machines ORDER BY machine_name ASC")->fetchAll();

// Dynamic query construction
$query = "
    SELECT mr.*, 
           m.machine_name, m.model_number, m.department, m.location,
           t.name as technician_name, t.employee_id as technician_code
    FROM maintenance_records mr
    JOIN machines m ON mr.machine_id = m.id
    LEFT JOIN technicians t ON mr.technician_id = t.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (m.machine_name LIKE :search OR mr.description LIKE :search OR mr.parts_replaced LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $query .= " AND mr.status = :status";
    $params[':status'] = $statusFilter;
}

if ($typeFilter !== '') {
    $query .= " AND mr.maintenance_type = :type";
    $params[':type'] = $typeFilter;
}

if ($machineFilter > 0) {
    $query .= " AND mr.machine_id = :machine_id";
    $params[':machine_id'] = $machineFilter;
}

$query .= " ORDER BY mr.scheduled_date DESC, mr.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

$statuses = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];
$types = ['Preventive', 'Corrective', 'Emergency'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter & Actions Bar -->
<div class="filter-bar">
    <form action="<?= url('maintenance/index.php') ?>" method="GET" class="filter-group" style="flex: 1;">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search tasks, machine, parts..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= ($statusFilter === $st) ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="form-select" style="width: auto;">
            <option value="">All Categories</option>
            <?php foreach ($types as $tp): ?>
                <option value="<?= $tp ?>" <?= ($typeFilter === $tp) ? 'selected' : '' ?>><?= $tp ?></option>
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

        <?php if ($search !== '' || $statusFilter !== '' || $typeFilter !== '' || $machineFilter > 0): ?>
            <a href="<?= url('maintenance/index.php') ?>" class="btn btn-secondary btn-sm" title="Clear Filters">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <div>
        <a href="<?= url('maintenance/add.php') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Schedule Maintenance
        </a>
    </div>
</div>

<!-- Maintenance Logs Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Equipment Servicing & Maintenance Logs</h2>
            <div class="card-subtitle">Showing <?= count($records) ?> recorded service operation(s)</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Equipment</th>
                    <th>Category</th>
                    <th>Scheduled</th>
                    <th>Completed</th>
                    <th>Technician</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <h3>No maintenance records found</h3>
                            <p>No records match your selected criteria. Try adjusting your search or add a new log.</p>
                            <a href="<?= url('maintenance/add.php') ?>" class="btn btn-primary btn-sm">Log New Maintenance</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): 
                        $isOverdue = ($r['status'] === 'Scheduled' && strtotime($r['scheduled_date']) < strtotime(date('Y-m-d')));
                    ?>
                        <tr class="<?= $isOverdue ? 'overdue-row' : '' ?>">
                            <td><strong>#<?= $r['id'] ?></strong></td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $r['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($r['machine_name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                    <?= htmlspecialchars($r['department']) ?> &bull; <?= htmlspecialchars($r['location']) ?>
                                </div>
                            </td>
                            <td><?= get_maintenance_type_badge($r['maintenance_type']) ?></td>
                            <td>
                                <?= format_date($r['scheduled_date']) ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-danger" style="margin-left: 4px; font-size: 10px;">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_date($r['completed_date']) ?></td>
                            <td>
                                <?= !empty($r['technician_name']) ? htmlspecialchars($r['technician_name']) : '<span class="text-muted">Unassigned</span>' ?>
                            </td>
                            <td><strong><?= format_currency($r['cost']) ?></strong></td>
                            <td><?= get_status_badge($r['status']) ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= url('maintenance/view.php?id=' . $r['id']) ?>" class="btn-icon" title="View Full Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('maintenance/edit.php?id=' . $r['id']) ?>" class="btn-icon" title="Edit / Mark Complete">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="<?= url('maintenance/delete.php?id=' . $r['id']) ?>" 
                                       class="btn-icon delete" 
                                       title="Delete Record"
                                       data-confirm-delete
                                       data-item-name="Maintenance Record #<?= $r['id'] ?>">
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
