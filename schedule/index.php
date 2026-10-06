<?php
// ====================================================================
// Dedicated Maintenance Schedule (Timeline & Overdue Tracking)
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Maintenance Schedule & Timeline';
$currentPage = 'schedule';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Schedule' => ''];

$viewFilter = trim($_GET['view'] ?? 'all');
$todayDate = date('Y-m-d');

// 1. Fetch counts for each schedule category
$allCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records")->fetchColumn();
$overdueCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status != 'Completed' AND scheduled_date < '{$todayDate}'")->fetchColumn();
$todayCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE scheduled_date = '{$todayDate}'")->fetchColumn();
$upcomingCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status != 'Completed' AND scheduled_date > '{$todayDate}'")->fetchColumn();
$completedCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'Completed'")->fetchColumn();

// 2. Fetch records according to active view filter
$query = "
    SELECT mr.*, 
           m.machine_name, m.model_number, m.department, m.location,
           t.name as technician_name, t.phone as technician_phone
    FROM maintenance_records mr
    JOIN machines m ON mr.machine_id = m.id
    LEFT JOIN technicians t ON mr.technician_id = t.id
    WHERE 1=1
";

switch ($viewFilter) {
    case 'overdue':
        $query .= " AND mr.status != 'Completed' AND mr.scheduled_date < '{$todayDate}' ORDER BY mr.scheduled_date ASC";
        break;
    case 'today':
        $query .= " AND mr.scheduled_date = '{$todayDate}' ORDER BY mr.status ASC";
        break;
    case 'upcoming':
        $query .= " AND mr.status != 'Completed' AND mr.scheduled_date > '{$todayDate}' ORDER BY mr.scheduled_date ASC";
        break;
    case 'completed':
        $query .= " AND mr.status = 'Completed' ORDER BY mr.completed_date DESC";
        break;
    default:
        // 'all': prioritize overdue and upcoming first
        $query .= " ORDER BY (mr.status != 'Completed' AND mr.scheduled_date < '{$todayDate}') DESC, mr.scheduled_date ASC";
        break;
}

$records = $pdo->query($query)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Overdue Priority Alert Notification -->
<?php if ($overdueCount > 0 && $viewFilter !== 'completed'): ?>
    <div class="alert alert-danger" style="margin-bottom: 24px;">
        <div class="alert-content">
            <i class="fa-solid fa-triangle-exclamation alert-icon" style="font-size: 20px;"></i>
            <div>
                <strong>Attention Required: <?= $overdueCount ?> Overdue Maintenance Task(s) Detected!</strong>
                <div style="font-size: 13px; margin-top: 2px;">
                    Certain industrial machines have exceeded their scheduled preventive or corrective service dates. Immediate inspection required.
                </div>
            </div>
        </div>
        <a href="<?= url('schedule/index.php?view=overdue') ?>" class="btn btn-danger btn-sm" style="white-space: nowrap;">
            Filter Overdue
        </a>
    </div>
<?php endif; ?>

<!-- Schedule Category Navigation Tabs -->
<div class="card" style="padding: 0; margin-bottom: 24px;">
    <div class="schedule-tabs" style="margin: 0; padding: 0 16px;">
        <a href="<?= url('schedule/index.php?view=all') ?>" class="schedule-tab <?= ($viewFilter === 'all') ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i> All Schedules
            <span class="count-badge"><?= $allCount ?></span>
        </a>
        <a href="<?= url('schedule/index.php?view=overdue') ?>" class="schedule-tab <?= ($viewFilter === 'overdue') ? 'active' : '' ?>" style="<?= $overdueCount > 0 ? 'color: var(--danger); font-weight: 700;' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Overdue
            <span class="count-badge" style="<?= $overdueCount > 0 ? 'background: #fee2e2; color: #dc2626;' : '' ?>"><?= $overdueCount ?></span>
        </a>
        <a href="<?= url('schedule/index.php?view=today') ?>" class="schedule-tab <?= ($viewFilter === 'today') ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-day"></i> Due Today
            <span class="count-badge"><?= $todayCount ?></span>
        </a>
        <a href="<?= url('schedule/index.php?view=upcoming') ?>" class="schedule-tab <?= ($viewFilter === 'upcoming') ? 'active' : '' ?>">
            <i class="fa-solid fa-forward"></i> Upcoming
            <span class="count-badge"><?= $upcomingCount ?></span>
        </a>
        <a href="<?= url('schedule/index.php?view=completed') ?>" class="schedule-tab <?= ($viewFilter === 'completed') ? 'active' : '' ?>">
            <i class="fa-solid fa-check-double"></i> Completed
            <span class="count-badge"><?= $completedCount ?></span>
        </a>
    </div>
</div>

<!-- Schedule Details Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">
                <i class="fa-solid fa-calendar-days"></i> 
                <?= ucfirst($viewFilter) ?> Maintenance Tasks
            </h2>
            <div class="card-subtitle">Showing <?= count($records) ?> scheduled service operation(s)</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('maintenance/add.php') ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Schedule New Service
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Scheduled Date</th>
                    <th>Timeline Status</th>
                    <th>Machine</th>
                    <th>Category</th>
                    <th>Floor Location</th>
                    <th>Assigned Tech</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h3>No records in this schedule view</h3>
                            <p>No maintenance tasks match the "<?= htmlspecialchars($viewFilter) ?>" criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): 
                        $isOverdue = ($r['status'] !== 'Completed' && strtotime($r['scheduled_date']) < strtotime($todayDate));
                        $isToday = ($r['scheduled_date'] === $todayDate);
                        $rowClass = $isOverdue ? 'overdue-row' : ($isToday ? 'today-row' : '');
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <strong><?= format_date($r['scheduled_date']) ?></strong>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'Completed'): ?>
                                    <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Finished</span>
                                <?php elseif ($isOverdue): ?>
                                    <span class="badge badge-danger"><i class="fa-solid fa-circle-exclamation"></i> Overdue</span>
                                <?php elseif ($isToday): ?>
                                    <span class="badge badge-warning"><i class="fa-solid fa-bell"></i> Due Today</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><i class="fa-solid fa-clock"></i> Upcoming</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $r['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($r['machine_name']) ?>
                                </a>
                                <div style="font-size: 11.5px; color: var(--text-muted);"><?= htmlspecialchars($r['model_number']) ?></div>
                            </td>
                            <td><?= get_maintenance_type_badge($r['maintenance_type']) ?></td>
                            <td>
                                <i class="fa-solid fa-location-dot" style="color: var(--danger); font-size: 11px;"></i>
                                <?= htmlspecialchars($r['location']) ?> (<?= htmlspecialchars($r['department']) ?>)
                            </td>
                            <td>
                                <?= !empty($r['technician_name']) ? htmlspecialchars($r['technician_name']) : '<span class="text-muted">Unassigned</span>' ?>
                            </td>
                            <td><?= get_status_badge($r['status']) ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= url('maintenance/view.php?id=' . $r['id']) ?>" class="btn-icon" title="View Order">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('maintenance/edit.php?id=' . $r['id']) ?>" class="btn-icon" title="Update / Complete">
                                        <i class="fa-solid fa-pen-to-square"></i>
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
