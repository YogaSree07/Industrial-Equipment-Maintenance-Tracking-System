<?php
// ====================================================================
// Maintenance Personnel & Technicians Directory
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Factory Technicians & Engineers';
$currentPage = 'technicians';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Technicians' => ''];

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT t.*,
           (SELECT COUNT(*) FROM machines m WHERE m.technician_id = t.id) as assigned_machines_count,
           (SELECT COUNT(*) FROM maintenance_records mr WHERE mr.technician_id = t.id AND mr.status != 'Completed') as active_maint_count,
           (SELECT COUNT(*) FROM breakdowns b WHERE b.technician_id = t.id AND b.status != 'Resolved') as active_breakdowns_count
    FROM technicians t
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (t.name LIKE :search OR t.employee_id LIKE :search OR t.specialization LIKE :search OR t.email LIKE :search OR t.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $query .= " AND t.status = :status";
    $params[':status'] = $statusFilter;
}

$query .= " ORDER BY t.status ASC, t.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$technicians = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Action & Filter Bar -->
<div class="filter-bar">
    <form action="<?= url('technicians/index.php') ?>" method="GET" class="filter-group" style="flex: 1;">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search technician name, ID, skill..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="status" class="form-select" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="Active" <?= ($statusFilter === 'Active') ? 'selected' : '' ?>>Active Only</option>
            <option value="Inactive" <?= ($statusFilter === 'Inactive') ? 'selected' : '' ?>>Inactive Only</option>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> Filter
        </button>

        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="<?= url('technicians/index.php') ?>" class="btn btn-secondary btn-sm" title="Clear Filters">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <div>
        <a href="<?= url('technicians/add.php') ?>" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Add New Technician
        </a>
    </div>
</div>

<!-- Technicians Table Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-users-gear"></i> Technical Staff & Specialists</h2>
            <div class="card-subtitle">Showing <?= count($technicians) ?> engineering team member(s)</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Technician Name</th>
                    <th>Department</th>
                    <th>Technical Specialization</th>
                    <th>Contact Phone</th>
                    <th>Email Address</th>
                    <th>Workload</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($technicians)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-users-gear"></i>
                            <h3>No technicians found</h3>
                            <p>No technician records match your criteria. Register a new technician to get started.</p>
                            <a href="<?= url('technicians/add.php') ?>" class="btn btn-primary btn-sm">Add First Technician</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($technicians as $t): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($t['employee_id']) ?></code></td>
                            <td>
                                <a href="<?= url('technicians/view.php?id=' . $t['id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($t['name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                    Joined: <?= format_date($t['joining_date']) ?>
                                </div>
                            </td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($t['department']) ?></span></td>
                            <td><?= htmlspecialchars($t['specialization']) ?></td>
                            <td>
                                <i class="fa-solid fa-phone" style="font-size: 11px; color: var(--text-light); margin-right: 4px;"></i>
                                <?= htmlspecialchars($t['phone']) ?>
                            </td>
                            <td>
                                <i class="fa-solid fa-envelope" style="font-size: 11px; color: var(--text-light); margin-right: 4px;"></i>
                                <?= htmlspecialchars($t['email']) ?>
                            </td>
                            <td>
                                <div style="font-size: 12px; display: flex; flex-direction: column; gap: 2px;">
                                    <span><strong><?= $t['assigned_machines_count'] ?></strong> machines</span>
                                    <?php if ($t['active_maint_count'] > 0 || $t['active_breakdowns_count'] > 0): ?>
                                        <span style="color: var(--warning); font-size: 11px;">
                                            <?= $t['active_maint_count'] ?> active jobs &bull; <?= $t['active_breakdowns_count'] ?> repairs
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= get_status_badge($t['status']) ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= url('technicians/view.php?id=' . $t['id']) ?>" class="btn-icon" title="View Profile & Assignments">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('technicians/edit.php?id=' . $t['id']) ?>" class="btn-icon" title="Edit Profile">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="<?= url('technicians/delete.php?id=' . $t['id']) ?>" 
                                       class="btn-icon delete" 
                                       title="Remove Technician"
                                       data-confirm-delete
                                       data-item-name="<?= htmlspecialchars($t['name']) ?>">
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
