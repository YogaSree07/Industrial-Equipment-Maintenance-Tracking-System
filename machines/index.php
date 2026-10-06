<?php
// ====================================================================
// Machines List & Inventory Management
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Industrial Equipment Inventory';
$currentPage = 'machines';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Machines' => ''];

// Filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');

// Build dynamic SQL query
$query = "
    SELECT m.*, t.name as technician_name, t.employee_id as technician_code
    FROM machines m
    LEFT JOIN technicians t ON m.technician_id = t.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (m.machine_name LIKE :search OR m.model_number LIKE :search OR m.manufacturer LIKE :search OR m.location LIKE :search OR m.machine_type LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $query .= " AND m.status = :status";
    $params[':status'] = $statusFilter;
}

if ($deptFilter !== '') {
    $query .= " AND m.department = :dept";
    $params[':dept'] = $deptFilter;
}

$query .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$machines = $stmt->fetchAll();

// List of all departments for filter dropdown
$departments = ['Production', 'Packaging', 'Assembly', 'Maintenance', 'Quality Control'];
$statuses = ['Working', 'Under Maintenance', 'Breakdown', 'Inactive'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Action Bar & Filter Bar -->
<div class="filter-bar">
    <form action="<?= url('machines/index.php') ?>" method="GET" class="filter-group" style="flex: 1;">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search machine, model, location..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= ($statusFilter === $st) ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>

        <select name="department" class="form-select" style="width: auto;">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept ?>" <?= ($deptFilter === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> Apply Filter
        </button>

        <?php if ($search !== '' || $statusFilter !== '' || $deptFilter !== ''): ?>
            <a href="<?= url('machines/index.php') ?>" class="btn btn-secondary btn-sm" title="Clear Filters">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <div>
        <a href="<?= url('machines/add.php') ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add New Machine
        </a>
    </div>
</div>

<!-- Machines Data Table Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-industry"></i> Registered Industrial Machines</h2>
            <div class="card-subtitle">Showing <?= count($machines) ?> piece(s) of equipment matching current filters</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Machine Name</th>
                    <th>Type / Model</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Assigned Tech</th>
                    <th>Next Maintenance</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($machines)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-industry"></i>
                            <h3>No equipment records found</h3>
                            <p>No machines match your query. Try resetting your search filters or add a new machine.</p>
                            <a href="<?= url('machines/add.php') ?>" class="btn btn-primary btn-sm">Add First Machine</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($machines as $m): ?>
                        <tr>
                            <td><strong>#<?= $m['id'] ?></strong></td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $m['id']) ?>" style="font-weight: 600; color: var(--primary);">
                                    <?= htmlspecialchars($m['machine_name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                    <?= htmlspecialchars($m['manufacturer']) ?>
                                </div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($m['machine_type']) ?></div>
                                <code style="font-size: 11px;"><?= htmlspecialchars($m['model_number']) ?></code>
                            </td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($m['department']) ?></span></td>
                            <td>
                                <i class="fa-solid fa-location-dot" style="color: var(--text-light); margin-right: 4px;"></i>
                                <?= htmlspecialchars($m['location']) ?>
                            </td>
                            <td><?= get_status_badge($m['status']) ?></td>
                            <td>
                                <?php if (!empty($m['technician_name'])): ?>
                                    <span title="<?= htmlspecialchars($m['technician_code']) ?>">
                                        <i class="fa-solid fa-user-gear" style="color: var(--text-light);"></i>
                                        <?= htmlspecialchars($m['technician_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $isOverdue = (!empty($m['next_maintenance_date']) && strtotime($m['next_maintenance_date']) < strtotime(date('Y-m-d')));
                                    echo format_date($m['next_maintenance_date']);
                                    if ($isOverdue && $m['status'] !== 'Inactive') {
                                        echo " <span class='badge badge-danger' style='font-size:10px; padding: 2px 6px;'>Due</span>";
                                    }
                                ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= url('machines/view.php?id=' . $m['id']) ?>" class="btn-icon" title="View Full Profile">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('machines/edit.php?id=' . $m['id']) ?>" class="btn-icon" title="Edit Machine">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="<?= url('machines/delete.php?id=' . $m['id']) ?>" 
                                       class="btn-icon delete" 
                                       title="Delete Machine"
                                       data-confirm-delete
                                       data-item-name="<?= htmlspecialchars($m['machine_name']) ?>">
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
