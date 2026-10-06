<?php
// ====================================================================
// Factory Maintenance & Reliability Reports
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Operations & Equipment Reports';
$currentPage = 'reports';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Reports' => ''];

// ==========================================
// 1. Machine Statistics
// ==========================================
$totalMachines = (int)$pdo->query("SELECT COUNT(*) FROM machines")->fetchColumn();

$machinesByStatusStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM machines GROUP BY status");
$machinesByStatus = ['Working' => 0, 'Under Maintenance' => 0, 'Breakdown' => 0, 'Inactive' => 0];
while ($r = $machinesByStatusStmt->fetch()) {
    $machinesByStatus[$r['status']] = (int)$r['cnt'];
}

$deptStmt = $pdo->query("SELECT department, COUNT(*) as cnt FROM machines GROUP BY department ORDER BY cnt DESC");
$machinesByDept = $deptStmt->fetchAll();

// ==========================================
// 2. Maintenance Statistics
// ==========================================
$totalMaintRecords = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records")->fetchColumn();
$preventiveCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE maintenance_type = 'Preventive'")->fetchColumn();
$correctiveCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE maintenance_type = 'Corrective'")->fetchColumn();
$emergencyCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE maintenance_type = 'Emergency'")->fetchColumn();

$completedMaintCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'Completed'")->fetchColumn();
$pendingMaintCount = (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status IN ('Scheduled', 'In Progress')")->fetchColumn();

$totalMaintCost = (float)$pdo->query("SELECT COALESCE(SUM(cost), 0) FROM maintenance_records")->fetchColumn();
$avgMaintCost = $totalMaintRecords > 0 ? ($totalMaintCost / $totalMaintRecords) : 0;

// ==========================================
// 3. Breakdown Statistics
// ==========================================
$totalBreakdowns = (int)$pdo->query("SELECT COUNT(*) FROM breakdowns")->fetchColumn();
$resolvedBreakdowns = (int)$pdo->query("SELECT COUNT(*) FROM breakdowns WHERE status = 'Resolved'")->fetchColumn();
$pendingBreakdowns = (int)$pdo->query("SELECT COUNT(*) FROM breakdowns WHERE status != 'Resolved'")->fetchColumn();

$sevStmt = $pdo->query("SELECT severity, COUNT(*) as cnt FROM breakdowns GROUP BY severity");
$breakdownsBySev = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
while ($r = $sevStmt->fetch()) {
    $breakdownsBySev[$r['severity']] = (int)$r['cnt'];
}

// ==========================================
// 4. Technician Workload Statistics
// ==========================================
$totalTechnicians = (int)$pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'Active'")->fetchColumn();
$techWorkloadStmt = $pdo->query("
    SELECT t.id, t.name, t.employee_id, t.department, t.specialization,
           (SELECT COUNT(*) FROM machines m WHERE m.technician_id = t.id) as assigned_machines,
           (SELECT COUNT(*) FROM maintenance_records mr WHERE mr.technician_id = t.id AND mr.status != 'Completed') as active_maint,
           (SELECT COUNT(*) FROM maintenance_records mr WHERE mr.technician_id = t.id AND mr.status = 'Completed') as completed_maint,
           (SELECT COUNT(*) FROM breakdowns b WHERE b.technician_id = t.id AND b.status != 'Resolved') as active_breakdowns,
           (SELECT COUNT(*) FROM breakdowns b WHERE b.technician_id = t.id AND b.status = 'Resolved') as resolved_breakdowns
    FROM technicians t
    WHERE t.status = 'Active'
    ORDER BY assigned_machines DESC
");
$technicianWorkload = $techWorkloadStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar & Print Button -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0;">Comprehensive Industrial Reliability Report</h2>
        <div style="color: var(--text-muted); font-size: 13px; margin-top: 2px;">
            Generated from active MySQL database on <?= date('F d, Y &bull; h:i A') ?>
        </div>
    </div>
    <div class="card-actions">
        <button type="button" onclick="window.print();" class="btn btn-secondary">
            <i class="fa-solid fa-print"></i> Print / Export Report
        </button>
    </div>
</div>

<!-- SECTION 1: MACHINES REPORTS -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-industry"></i> 1. Machinery & Equipment Availability Overview</h2>
        <span class="badge badge-primary"><?= $totalMachines ?> Total Assets</span>
    </div>
    <div class="card-body">
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Total Inventory</span>
                    <span class="stat-card-value"><?= $totalMachines ?></span>
                </div>
                <div class="stat-card-icon blue"><i class="fa-solid fa-industry"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Working Assets</span>
                    <span class="stat-card-value" style="color: var(--success);"><?= $machinesByStatus['Working'] ?></span>
                    <span class="stat-card-hint"><?= $totalMachines > 0 ? round(($machinesByStatus['Working'] / $totalMachines) * 100, 1) : 0 ?>% Availability</span>
                </div>
                <div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">In Maintenance</span>
                    <span class="stat-card-value" style="color: var(--warning);"><?= $machinesByStatus['Under Maintenance'] ?></span>
                </div>
                <div class="stat-card-icon yellow"><i class="fa-solid fa-wrench"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Breakdown Stoppages</span>
                    <span class="stat-card-value" style="color: var(--danger);"><?= $machinesByStatus['Breakdown'] ?></span>
                </div>
                <div class="stat-card-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: center;">
            <div>
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);">Equipment Distribution by Plant Department</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Machine Count</th>
                                <th>Share of Factory</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($machinesByDept as $d): 
                                $pct = $totalMachines > 0 ? round(($d['cnt'] / $totalMachines) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($d['department']) ?></strong></td>
                                    <td><?= $d['cnt'] ?> machines</td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="flex: 1; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                                <div style="width: <?= $pct ?>%; height: 100%; background: var(--primary);"></div>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 600; min-width: 40px;"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="chart-container" style="height: 240px;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: MAINTENANCE REPORTS -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> 2. Maintenance Operations & Cost Analytics</h2>
        <span class="badge badge-secondary"><?= $totalMaintRecords ?> Total Work Orders</span>
    </div>
    <div class="card-body">
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Total Expenditure</span>
                    <span class="stat-card-value" style="color: var(--primary);"><?= format_currency($totalMaintCost) ?></span>
                    <span class="stat-card-hint">Avg <?= format_currency($avgMaintCost) ?> / job</span>
                </div>
                <div class="stat-card-icon blue"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Preventive Servicing</span>
                    <span class="stat-card-value"><?= $preventiveCount ?></span>
                    <span class="stat-card-hint">Scheduled proactive care</span>
                </div>
                <div class="stat-card-icon teal"><i class="fa-solid fa-shield-halved"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Corrective & Emergency</span>
                    <span class="stat-card-value"><?= $correctiveCount + $emergencyCount ?></span>
                    <span class="stat-card-hint"><?= $emergencyCount ?> emergency interventions</span>
                </div>
                <div class="stat-card-icon red"><i class="fa-solid fa-fire-extinguisher"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Completion Ratio</span>
                    <span class="stat-card-value" style="color: var(--success);"><?= $completedMaintCount ?></span>
                    <span class="stat-card-hint"><?= $pendingMaintCount ?> work order(s) pending</span>
                </div>
                <div class="stat-card-icon green"><i class="fa-solid fa-check-double"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: BREAKDOWN REPORTS -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> 3. Machinery Breakdown & Downtime Metrics</h2>
        <span class="badge badge-danger"><?= $totalBreakdowns ?> Total Incidents</span>
    </div>
    <div class="card-body">
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Total Breakdowns</span>
                    <span class="stat-card-value"><?= $totalBreakdowns ?></span>
                </div>
                <div class="stat-card-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Resolved Tickets</span>
                    <span class="stat-card-value" style="color: var(--success);"><?= $resolvedBreakdowns ?></span>
                    <span class="stat-card-hint"><?= $totalBreakdowns > 0 ? round(($resolvedBreakdowns / $totalBreakdowns) * 100, 1) : 0 ?>% Resolution Rate</span>
                </div>
                <div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Critical Incidents</span>
                    <span class="stat-card-value" style="color: var(--danger);"><?= $breakdownsBySev['Critical'] ?></span>
                    <span class="stat-card-hint">High priority production halts</span>
                </div>
                <div class="stat-card-icon red"><i class="fa-solid fa-skull-crossbones"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-info">
                    <span class="stat-card-label">Active Stoppages</span>
                    <span class="stat-card-value" style="color: var(--warning);"><?= $pendingBreakdowns ?></span>
                    <span class="stat-card-hint">Currently under repair</span>
                </div>
                <div class="stat-card-icon yellow"><i class="fa-solid fa-clock"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 4: TECHNICIAN WORKLOAD REPORT -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-users-gear"></i> 4. Technical Staff Workload & Productivity Report</h2>
            <div class="card-subtitle">Showing operational output of all <?= count($technicianWorkload) ?> active technical personnel</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Technician</th>
                    <th>Department</th>
                    <th>Specialization</th>
                    <th>Assigned Machines</th>
                    <th>Pending Maintenance</th>
                    <th>Completed Maintenance</th>
                    <th>Active Breakdowns</th>
                    <th>Resolved Breakdowns</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($technicianWorkload as $tw): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($tw['employee_id']) ?></code></td>
                        <td>
                            <a href="<?= url('technicians/view.php?id=' . $tw['id']) ?>" style="font-weight: 600;">
                                <?= htmlspecialchars($tw['name']) ?>
                            </a>
                        </td>
                        <td><span class="badge badge-secondary"><?= htmlspecialchars($tw['department']) ?></span></td>
                        <td><?= htmlspecialchars($tw['specialization']) ?></td>
                        <td><strong><?= $tw['assigned_machines'] ?></strong></td>
                        <td>
                            <?php if ($tw['active_maint'] > 0): ?>
                                <span class="badge badge-warning"><?= $tw['active_maint'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-success"><?= $tw['completed_maint'] ?></span></td>
                        <td>
                            <?php if ($tw['active_breakdowns'] > 0): ?>
                                <span class="badge badge-danger"><?= $tw['active_breakdowns'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-success"><?= $tw['resolved_breakdowns'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Render Department Chart -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptLabels = <?= json_encode(array_column($machinesByDept, 'department')) ?>;
    const deptCounts = <?= json_encode(array_column($machinesByDept, 'cnt')) ?>;

    const ctx = document.getElementById('deptChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: deptLabels,
            datasets: [{
                label: 'Machines',
                data: deptCounts,
                backgroundColor: '#3b82f6',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
