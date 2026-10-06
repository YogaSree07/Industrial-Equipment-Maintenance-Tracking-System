<?php
// ====================================================================
// Report Equipment Breakdown Incident
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Report Machine Breakdown';
$currentPage = 'breakdowns';
$breadcrumbs = [
    'Dashboard'  => 'dashboard.php',
    'Breakdowns' => 'breakdowns/index.php',
    'Report'     => ''
];

$preselectedMachineId = (int)($_GET['machine_id'] ?? 0);

// Fetch machines list
$machines = $pdo->query("SELECT id, machine_name, department, location, status, technician_id FROM machines ORDER BY machine_name ASC")->fetchAll();

// Fetch active technicians
$technicians = $pdo->query("SELECT id, name, employee_id, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$errors = [];
$formData = [
    'machine_id'          => $preselectedMachineId > 0 ? $preselectedMachineId : '',
    'reported_date'       => date('Y-m-d\TH:i'),
    'problem_description' => '',
    'severity'            => 'High',
    'technician_id'       => '',
    'start_time'          => date('Y-m-d\TH:i'),
    'status'              => 'Reported',
    'resolution_notes'    => ''
];

// If machine preselected, auto-assign lead technician
if ($preselectedMachineId > 0) {
    foreach ($machines as $m) {
        if ($m['id'] == $preselectedMachineId && !empty($m['technician_id'])) {
            $formData['technician_id'] = $m['technician_id'];
            break;
        }
    }
}

$severities = ['Low', 'Medium', 'High', 'Critical'];
$statuses = ['Reported', 'In Progress', 'Resolved'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['machine_id']          = (int)($_POST['machine_id'] ?? 0);
    $formData['reported_date']       = !empty($_POST['reported_date']) ? str_replace('T', ' ', $_POST['reported_date']) . ':00' : date('Y-m-d H:i:s');
    $formData['problem_description'] = trim($_POST['problem_description'] ?? '');
    $formData['severity']            = trim($_POST['severity'] ?? 'Medium');
    $formData['technician_id']       = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    $formData['start_time']          = !empty($_POST['start_time']) ? str_replace('T', ' ', $_POST['start_time']) . ':00' : null;
    $formData['status']              = trim($_POST['status'] ?? 'Reported');
    $formData['resolution_notes']    = trim($_POST['resolution_notes'] ?? '');

    // Validation
    if ($formData['machine_id'] <= 0) {
        $errors[] = 'Please select the affected machine.';
    }
    if (empty($formData['problem_description'])) {
        $errors[] = 'Problem description is required to file a breakdown report.';
    }
    if (!in_array($formData['severity'], $severities)) {
        $errors[] = 'Invalid breakdown severity selected.';
    }
    if (!in_array($formData['status'], $statuses)) {
        $errors[] = 'Invalid breakdown status selected.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert breakdown incident report
            $stmt = $pdo->prepare("
                INSERT INTO breakdowns (
                    machine_id, reported_date, problem_description, severity,
                    technician_id, start_time, status, resolution_notes
                ) VALUES (
                    :machine_id, :reported_date, :problem_description, :severity,
                    :technician_id, :start_time, :status, :resolution_notes
                )
            ");
            $stmt->execute([
                ':machine_id'          => $formData['machine_id'],
                ':reported_date'       => $formData['reported_date'],
                ':problem_description' => $formData['problem_description'],
                ':severity'            => $formData['severity'],
                ':technician_id'       => $formData['technician_id'],
                ':start_time'          => $formData['start_time'],
                ':status'              => $formData['status'],
                ':resolution_notes'    => $formData['resolution_notes']
            ]);

            // 2. IMPORTANT BUSINESS LOGIC:
            // Automatically switch the affected machine's status to "Breakdown"
            $machineUpdateStmt = $pdo->prepare("UPDATE machines SET status = 'Breakdown' WHERE id = :id");
            $machineUpdateStmt->execute([':id' => $formData['machine_id']]);

            $pdo->commit();
            flash_message('success', 'Breakdown incident logged! Machine status has been automatically updated to "Breakdown".');
            redirect('breakdowns/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error saving breakdown report: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Report Machine Breakdown Incident</h2>
            <div class="card-subtitle">Log unexpected stoppage, malfunction, or safety shutdown</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('breakdowns/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Incident Logs
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div>
                        <strong>Please resolve the following issues:</strong>
                        <ul style="margin: 6px 0 0 20px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #9a3412;">
            <i class="fa-solid fa-circle-info"></i>
            <strong>System Automation Notice:</strong> Filing this breakdown report will automatically change the machine's operational status to <strong>"Breakdown"</strong> on the factory dashboard.
        </div>

        <form action="<?= url('breakdowns/add.php') ?>" method="POST">
            <div class="form-grid">
                <!-- Affected Machine -->
                <div class="form-group col-full">
                    <label for="machine_id" class="form-label">Affected Industrial Machine <span class="required">*</span></label>
                    <select id="machine_id" name="machine_id" class="form-select" required>
                        <option value="">-- Select Affected Equipment --</option>
                        <?php foreach ($machines as $mac): ?>
                            <option value="<?= $mac['id'] ?>" <?= ($formData['machine_id'] == $mac['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mac['machine_name']) ?> &mdash; [<?= htmlspecialchars($mac['department']) ?> / <?= htmlspecialchars($mac['location']) ?>] (Current: <?= htmlspecialchars($mac['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Severity -->
                <div class="form-group">
                    <label for="severity" class="form-label">Incident Severity <span class="required">*</span></label>
                    <select id="severity" name="severity" class="form-select" required>
                        <?php foreach ($severities as $sev): ?>
                            <option value="<?= $sev ?>" <?= ($formData['severity'] === $sev) ? 'selected' : '' ?>><?= $sev ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Initial Status -->
                <div class="form-group">
                    <label for="status" class="form-label">Incident Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($formData['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reported Date/Time -->
                <div class="form-group">
                    <label for="reported_date" class="form-label">Incident Timestamp <span class="required">*</span></label>
                    <input type="datetime-local" id="reported_date" name="reported_date" class="form-control" value="<?= htmlspecialchars($formData['reported_date']) ?>" required>
                </div>

                <!-- Response Start Time -->
                <div class="form-group">
                    <label for="start_time" class="form-label">Technician Dispatch / Start Time</label>
                    <input type="datetime-local" id="start_time" name="start_time" class="form-control" value="<?= htmlspecialchars($formData['start_time']) ?>">
                </div>

                <!-- Assigned Technician -->
                <div class="form-group col-full">
                    <label for="technician_id" class="form-label">Assigned Emergency Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Assign Emergency Specialist --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($formData['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['department']) ?> &bull; <?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Problem Description -->
                <div class="form-group col-full">
                    <label for="problem_description" class="form-label">Problem Symptoms / Alarm Details <span class="required">*</span></label>
                    <textarea id="problem_description" name="problem_description" class="form-control" rows="4" placeholder="Detail error codes, abnormal noises, smoke, pressure drop, electrical tripping..." required><?= htmlspecialchars($formData['problem_description']) ?></textarea>
                </div>

                <!-- Initial Inspection Notes -->
                <div class="form-group col-full">
                    <label for="resolution_notes" class="form-label">Initial Assessment / Triage Notes</label>
                    <textarea id="resolution_notes" name="resolution_notes" class="form-control" rows="3" placeholder="Preliminary checks conducted or required spare parts..."><?= htmlspecialchars($formData['resolution_notes']) ?></textarea>
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('breakdowns/index.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-bullhorn"></i> Submit Breakdown Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
