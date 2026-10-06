<?php
// ====================================================================
// Dashboard (Operations Overview & Live Analytics)
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Operations Dashboard';
$currentPage = 'dashboard';
$breadcrumbs = ['Dashboard' => ''];

// 1. Fetch Dynamic Summary KPI Counts
$totalMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines")->fetchColumn();
$workingMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines WHERE status = 'Working'")->fetchColumn();
$underMaintenanceMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines WHERE status = 'Under Maintenance'")->fetchColumn();
$breakdownMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines WHERE status = 'Breakdown'")->fetchColumn();
$inactiveMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines WHERE status = 'Inactive'")->fetchColumn();

$totalTechnicians = (int)$pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'Active'")->fetchColumn();
$upcomingMaintenanceCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status IN ('Scheduled', 'In Progress') AND scheduled_date >= CURDATE()")->fetchColumn();

// 2. Data for Charts
// Chart A: Machines by Status
$statusChartData = [
    'labels' => ['Working', 'Under Maintenance', 'Breakdown', 'Inactive'],
    'data'   => [$workingMachines, $underMaintenanceMachines, $breakdownMachines, $inactiveMachines]
];

// Chart B: Maintenance by Type
$maintTypeStmt = $pdo->query("
    SELECT maintenance_type, COUNT(*) as total 
    FROM maintenance_records 
    GROUP BY maintenance_type
");
$maintTypes = ['Preventive' => 0, 'Corrective' => 0, 'Emergency' => 0];
while ($row = $maintTypeStmt->fetch()) {
    $maintTypes[$row['maintenance_type']] = (int)$row['total'];
}

// Chart C: Breakdowns by Severity
$sevStmt = $pdo->query("
    SELECT severity, COUNT(*) as total 
    FROM breakdowns 
    GROUP BY severity
");
$severities = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
while ($row = $sevStmt->fetch()) {
    $severities[$row['severity']] = (int)$row['total'];
}

// 3. Recent Maintenance Records (Last 5)
$recentMaintStmt = $pdo->query("
    SELECT mr.*, m.machine_name, t.name as technician_name 
    FROM maintenance_records mr
    JOIN machines m ON mr.machine_id = m.id
    LEFT JOIN technicians t ON mr.technician_id = t.id
    ORDER BY mr.scheduled_date DESC, mr.id DESC
    LIMIT 5
");
$recentMaintenance = $recentMaintStmt->fetchAll();

// 4. Recent Breakdowns (Last 5)
$recentBreakdownStmt = $pdo->query("
    SELECT b.*, m.machine_name, t.name as technician_name 
    FROM breakdowns b
    JOIN machines m ON b.machine_id = m.id
    LEFT JOIN technicians t ON b.technician_id = t.id
    ORDER BY b.reported_date DESC
    LIMIT 5
");
$recentBreakdowns = $recentBreakdownStmt->fetchAll();

// 5. Upcoming Maintenance Schedules
$upcomingStmt = $pdo->query("
    SELECT mr.*, m.machine_name, m.location, t.name as technician_name 
    FROM maintenance_records mr
    JOIN machines m ON mr.machine_id = m.id
    LEFT JOIN technicians t ON mr.technician_id = t.id
    WHERE mr.status IN ('Scheduled', 'In Progress')
    ORDER BY mr.scheduled_date ASC
    LIMIT 5
");
$upcomingSchedules = $upcomingStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- KPI Stat Cards Row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Total Machines</span>
            <span class="stat-card-value"><?= $totalMachines ?></span>
            <span class="stat-card-hint"><?= $inactiveMachines ?> decommissioned / inactive</span>
        </div>
        <div class="stat-card-icon blue">
            <i class="fa-solid fa-industry"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Working Machines</span>
            <span class="stat-card-value" style="color: var(--success);"><?= $workingMachines ?></span>
            <span class="stat-card-hint">
                <?= $totalMachines > 0 ? round(($workingMachines / $totalMachines) * 100, 1) : 0 ?>% operational availability
            </span>
        </div>
        <div class="stat-card-icon green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Under Maintenance</span>
            <span class="stat-card-value" style="color: var(--warning);"><?= $underMaintenanceMachines ?></span>
            <span class="stat-card-hint">Servicing in progress</span>
        </div>
        <div class="stat-card-icon yellow">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Breakdowns</span>
            <span class="stat-card-value" style="color: var(--danger);"><?= $breakdownMachines ?></span>
            <span class="stat-card-hint">Immediate repair required</span>
        </div>
        <div class="stat-card-icon red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Active Technicians</span>
            <span class="stat-card-value"><?= $totalTechnicians ?></span>
            <span class="stat-card-hint">Floor specialists available</span>
        </div>
        <div class="stat-card-icon purple">
            <i class="fa-solid fa-users-gear"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-info">
            <span class="stat-card-label">Upcoming Maintenance</span>
            <span class="stat-card-value"><?= $upcomingMaintenanceCount ?></span>
            <span class="stat-card-hint">Planned for this period</span>
        </div>
        <div class="stat-card-icon teal">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
    </div>
</div>

<!-- Visual Analytics Charts Row -->
<div class="charts-grid">
    <!-- Chart 1: Machine Health & Status -->
    <div class="chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-chart-pie" style="color: var(--primary); margin-right: 6px;"></i> Machine Status Distribution
            </h3>
            <span class="badge badge-secondary"><?= $totalMachines ?> Total</span>
        </div>
        <div class="chart-container">
            <canvas id="machineStatusChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Maintenance by Type -->
    <div class="chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-chart-bar" style="color: var(--primary); margin-right: 6px;"></i> Maintenance Work by Category
            </h3>
            <span class="badge badge-secondary"><?= array_sum($maintTypes) ?> Total Jobs</span>
        </div>
        <div class="chart-container">
            <canvas id="maintTypeChart"></canvas>
        </div>
    </div>

    <!-- Chart 3: Breakdown Severity Analysis -->
    <div class="chart-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-chart-column" style="color: var(--danger); margin-right: 6px;"></i> Breakdowns by Severity
            </h3>
            <span class="badge badge-secondary"><?= array_sum($severities) ?> Reports</span>
        </div>
        <div class="chart-container">
            <canvas id="breakdownSeverityChart"></canvas>
        </div>
    </div>
</div>

<!-- Section: Upcoming Schedules & Quick Actions -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-calendar-days"></i> Upcoming Maintenance Tasks</h2>
            <div class="card-subtitle">Equipment requiring scheduled servicing in the upcoming days</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('schedule/index.php') ?>" class="btn btn-secondary btn-sm">
                View Full Schedule &rarr;
            </a>
            <a href="<?= url('maintenance/add.php') ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Schedule Maintenance
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Scheduled Date</th>
                    <th>Machine</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Assigned Technician</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcomingSchedules)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h3>No upcoming maintenance scheduled</h3>
                            <p>All machinery is currently operating up-to-date with maintenance requirements.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcomingSchedules as $sched): 
                        $isOverdue = ($sched['status'] === 'Scheduled' && strtotime($sched['scheduled_date']) < strtotime(date('Y-m-d')));
                        $isToday = ($sched['scheduled_date'] === date('Y-m-d'));
                        $rowClass = $isOverdue ? 'overdue-row' : ($isToday ? 'today-row' : '');
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <strong><?= format_date($sched['scheduled_date']) ?></strong>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-danger" style="margin-left: 6px; font-size: 10px;">Overdue</span>
                                <?php elseif ($isToday): ?>
                                    <span class="badge badge-warning" style="margin-left: 6px; font-size: 10px;">Due Today</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('machines/view.php?id=' . $sched['machine_id']) ?>" style="font-weight: 600;">
                                    <?= htmlspecialchars($sched['machine_name']) ?>
                                </a>
                            </td>
                            <td><?= get_maintenance_type_badge($sched['maintenance_type']) ?></td>
                            <td><i class="fa-solid fa-location-dot" style="color: var(--text-light); margin-right: 4px;"></i> <?= htmlspecialchars($sched['location']) ?></td>
                            <td><?= htmlspecialchars($sched['technician_name'] ?? 'Unassigned') ?></td>
                            <td><?= get_status_badge($sched['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('maintenance/view.php?id=' . $sched['id']) ?>" class="btn-icon" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= url('maintenance/edit.php?id=' . $sched['id']) ?>" class="btn-icon" title="Update / Complete">
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

<!-- Two-Column Layout: Recent Breakdowns & Recent Maintenance Records -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 24px;">
    <!-- Recent Breakdowns -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Recent Breakdown Incidents</h2>
                <div class="card-subtitle">Active and recently resolved equipment breakdown alarms</div>
            </div>
            <div class="card-actions">
                <a href="<?= url('breakdowns/add.php') ?>" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-bullhorn"></i> Report Breakdown
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reported</th>
                        <th>Machine</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBreakdowns)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fa-solid fa-shield-check"></i>
                                <h3>No breakdown records reported</h3>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBreakdowns as $bk): ?>
                            <tr>
                                <td><?= format_datetime($bk['reported_date']) ?></td>
                                <td>
                                    <a href="<?= url('machines/view.php?id=' . $bk['machine_id']) ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($bk['machine_name']) ?>
                                    </a>
                                </td>
                                <td><?= get_severity_badge($bk['severity']) ?></td>
                                <td><?= get_status_badge($bk['status']) ?></td>
                                <td style="text-align: right;">
                                    <a href="<?= url('breakdowns/view.php?id=' . $bk['id']) ?>" class="btn-icon" title="View Breakdown">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= url('breakdowns/edit.php?id=' . $bk['id']) ?>" class="btn-icon" title="Edit / Resolve">
                                        <i class="fa-solid fa-wrench"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Maintenance History -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Maintenance Activity</h2>
                <div class="card-subtitle">Log of completed and ongoing servicing records</div>
            </div>
            <div class="card-actions">
                <a href="<?= url('maintenance/index.php') ?>" class="btn btn-secondary btn-sm">
                    View All Logs &rarr;
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Machine</th>
                        <th>Type</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMaintenance)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fa-solid fa-wrench"></i>
                                <h3>No maintenance activity logged</h3>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentMaintenance as $rm): ?>
                            <tr>
                                <td><?= format_date($rm['scheduled_date']) ?></td>
                                <td>
                                    <a href="<?= url('machines/view.php?id=' . $rm['machine_id']) ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($rm['machine_name']) ?>
                                    </a>
                                </td>
                                <td><?= get_maintenance_type_badge($rm['maintenance_type']) ?></td>
                                <td><strong><?= format_currency($rm['cost']) ?></strong></td>
                                <td><?= get_status_badge($rm['status']) ?></td>
                                <td style="text-align: right;">
                                    <a href="<?= url('maintenance/view.php?id=' . $rm['id']) ?>" class="btn-icon" title="View Details">
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
</div>

<!-- Initialize Interactive Dashboard Charts with Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Machine Status Doughnut Chart
    const statusCtx = document.getElementById('machineStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusChartData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($statusChartData['data']) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#94a3b8'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Inter', size: 12 } } }
            },
            cutout: '65%'
        }
    });

    // 2. Maintenance by Type Bar Chart
    const typeCtx = document.getElementById('maintTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: ['Preventive', 'Corrective', 'Emergency'],
            datasets: [{
                label: 'Maintenance Jobs',
                data: [<?= $maintTypes['Preventive'] ?>, <?= $maintTypes['Corrective'] ?>, <?= $maintTypes['Emergency'] ?>],
                backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Inter' } }, grid: { color: '#f1f5f9' } },
                x: { font: { family: 'Inter' }, grid: { display: false } }
            }
        }
    });

    // 3. Breakdown Severity Bar Chart
    const sevCtx = document.getElementById('breakdownSeverityChart').getContext('2d');
    new Chart(sevCtx, {
        type: 'bar',
        data: {
            labels: ['Low', 'Medium', 'High', 'Critical'],
            datasets: [{
                label: 'Incidents',
                data: [<?= $severities['Low'] ?>, <?= $severities['Medium'] ?>, <?= $severities['High'] ?>, <?= $severities['Critical'] ?>],
                backgroundColor: ['#0284c7', '#f59e0b', '#ea580c', '#ef4444'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Inter' } }, grid: { color: '#f1f5f9' } },
                x: { font: { family: 'Inter' }, grid: { display: false } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
