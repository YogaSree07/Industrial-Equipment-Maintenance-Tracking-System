<?php
// ====================================================================
// Edit / Resolve Machine Breakdown Incident
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid breakdown incident ID.', 'error');
    redirect('breakdowns/index.php');
}

$stmt = $pdo->prepare("
    SELECT b.*, m.machine_name, m.status as current_machine_status
    FROM breakdowns b
    JOIN machines m ON b.machine_id = m.id
    WHERE b.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$breakdown = $stmt->fetch();

if (!$breakdown) {
    flash_message('error', 'Breakdown incident record not found.', 'error');
    redirect('breakdowns/index.php');
}

$pageTitle = 'Breakdown Ticket #' . $id;
$currentPage = 'breakdowns';
$breadcrumbs = [
    'Dashboard'  => 'dashboard.php',
    'Breakdowns' => 'breakdowns/index.php',
    'Edit #' . $id => ''
];

$machines = $pdo->query("SELECT id, machine_name FROM machines ORDER BY machine_name ASC")->fetchAll();
$technicians = $pdo->query("SELECT id, name, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$errors = [];
$severities = ['Critical', 'High', 'Medium', 'Low'];
$statuses = ['Reported', 'In Progress', 'Resolved'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $breakdown['machine_id']          = (int)($_POST['machine_id'] ?? 0);
    $breakdown['severity']            = trim($_POST['severity'] ?? 'Medium');
    $breakdown['technician_id']       = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    $breakdown['problem_description'] = trim($_POST['problem_description'] ?? '');
    $breakdown['resolution_notes']    = trim($_POST['resolution_notes'] ?? '');
    $breakdown['status']              = trim($_POST['status'] ?? 'Reported');
    
    $reportedDate = !empty($_POST['reported_date']) ? str_replace('T', ' ', $_POST['reported_date']) : $breakdown['reported_date'];
    $startTime = !empty($_POST['start_time']) ? str_replace('T', ' ', $_POST['start_time']) : null;
    $resolutionTime = !empty($_POST['resolution_time']) ? str_replace('T', ' ', $_POST['resolution_time']) : null;

    $machineAction = trim($_POST['machine_action'] ?? 'no_change');

    if ($breakdown['machine_id'] <= 0) {
        $errors[] = 'Please select a machine.';
    }
    if (empty($breakdown['problem_description'])) {
        $errors[] = 'Problem description cannot be blank.';
    }
    if (!in_array($breakdown['severity'], $severities)) {
        $errors[] = 'Invalid severity rating.';
    }
    if (!in_array($breakdown['status'], $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // If marked resolved, auto-stamp resolution time if left empty
            if ($breakdown['status'] === 'Resolved' && empty($resolutionTime)) {
                $resolutionTime = date('Y-m-d H:i:s');
            }

            $updateSql = "
                UPDATE breakdowns SET
                    machine_id = :machine_id,
                    reported_date = :reported_date,
                    problem_description = :problem_description,
                    severity = :severity,
                    technician_id = :technician_id,
                    start_time = :start_time,
                    resolution_time = :resolution_time,
                    status = :status,
                    resolution_notes = :resolution_notes
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                ':machine_id'          => $breakdown['machine_id'],
                ':reported_date'       => $reportedDate,
                ':problem_description' => $breakdown['problem_description'],
                ':severity'            => $breakdown['severity'],
                ':technician_id'       => $breakdown['technician_id'],
                ':start_time'          => $startTime,
                ':resolution_time'     => $resolutionTime,
                ':status'              => $breakdown['status'],
                ':resolution_notes'    => $breakdown['resolution_notes'],
                ':id'                  => $id
            ]);

            // Synchronize machine status upon resolution or state transition
            if ($breakdown['status'] === 'Resolved' && $machineAction === 'set_working') {
                $pdo->prepare("UPDATE machines SET status = 'Working' WHERE id = :mid")->execute([':mid' => $breakdown['machine_id']]);
            } elseif ($breakdown['status'] === 'Resolved' && $machineAction === 'set_maintenance') {
                $pdo->prepare("UPDATE machines SET status = 'Under Maintenance' WHERE id = :mid")->execute([':mid' => $breakdown['machine_id']]);
            } elseif ($breakdown['status'] !== 'Resolved') {
                // If reopened or in progress, make sure machine remains marked Breakdown
                $pdo->prepare("UPDATE machines SET status = 'Breakdown' WHERE id = :mid")->execute([':mid' => $breakdown['machine_id']]);
            }

            $pdo->commit();
            flash_message('success', "Breakdown ticket #{$id} updated successfully.");
            redirect('breakdowns/view.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error updating breakdown ticket: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Update Breakdown Ticket #<?= $id ?></h2>
            <div class="card-subtitle">Manage repair progress, assign technician, and log resolution</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('breakdowns/view.php?id=' . $id) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-eye"></i> View Ticket
            </a>
            <a href="<?= url('breakdowns/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Incident List
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div>
                        <strong>Please resolve the following:</strong>
                        <ul style="margin: 6px 0 0 20px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= url('breakdowns/edit.php?id=' . $id) ?>" method="POST">
            <div class="form-grid">
                <!-- Machine -->
                <div class="form-group col-full">
                    <label for="machine_id" class="form-label">Affected Machine <span class="required">*</span></label>
                    <select id="machine_id" name="machine_id" class="form-select" required>
                        <?php foreach ($machines as $mac): ?>
                            <option value="<?= $mac['id'] ?>" <?= ($breakdown['machine_id'] == $mac['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mac['machine_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Severity -->
                <div class="form-group">
                    <label for="severity" class="form-label">Incident Severity <span class="required">*</span></label>
                    <select id="severity" name="severity" class="form-select" required>
                        <?php foreach ($severities as $sev): ?>
                            <option value="<?= $sev ?>" <?= ($breakdown['severity'] === $sev) ? 'selected' : '' ?>><?= $sev ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ticket Status -->
                <div class="form-group">
                    <label for="status" class="form-label">Breakdown Resolution Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($breakdown['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Incident Reported Date -->
                <div class="form-group">
                    <label for="reported_date" class="form-label">Reported Timestamp <span class="required">*</span></label>
                    <input type="datetime-local" id="reported_date" name="reported_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($breakdown['reported_date'])) ?>" required>
                </div>

                <!-- Repair Start Time -->
                <div class="form-group">
                    <label for="start_time" class="form-label">Technician Dispatch / Start Time</label>
                    <input type="datetime-local" id="start_time" name="start_time" class="form-control" value="<?= !empty($breakdown['start_time']) ? date('Y-m-d\TH:i', strtotime($breakdown['start_time'])) : '' ?>">
                </div>

                <!-- Resolution Timestamp -->
                <div class="form-group">
                    <label for="resolution_time" class="form-label">Resolution Timestamp (When Fixed)</label>
                    <input type="datetime-local" id="resolution_time" name="resolution_time" class="form-control" value="<?= !empty($breakdown['resolution_time']) ? date('Y-m-d\TH:i', strtotime($breakdown['resolution_time'])) : '' ?>">
                </div>

                <!-- Assigned Technician -->
                <div class="form-group">
                    <label for="technician_id" class="form-label">Assigned Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($breakdown['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Problem Description -->
                <div class="form-group col-full">
                    <label for="problem_description" class="form-label">Problem Symptoms / Alarm Logs <span class="required">*</span></label>
                    <textarea id="problem_description" name="problem_description" class="form-control" rows="3" required><?= htmlspecialchars($breakdown['problem_description']) ?></textarea>
                </div>

                <!-- Resolution Notes -->
                <div class="form-group col-full">
                    <label for="resolution_notes" class="form-label">Root Cause & Resolution Summary</label>
                    <textarea id="resolution_notes" name="resolution_notes" class="form-control" rows="4" placeholder="Detail corrective action taken, parts replaced, calibration tests completed..."><?= htmlspecialchars($breakdown['resolution_notes'] ?? '') ?></textarea>
                </div>

                <!-- Machine Status Post-Resolution -->
                <div class="form-group col-full" style="background: #f1f5f9; padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <label class="form-label" style="font-weight: 700; color: #0f172a;">
                        <i class="fa-solid fa-robot"></i> Machine Operational Status Action upon Saving:
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 6px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                            <input type="radio" name="machine_action" value="set_working" <?= ($breakdown['status'] === 'Resolved' || empty($breakdown['resolution_time'])) ? 'checked' : '' ?>>
                            <span>Set machine status back to <strong>"Working"</strong> (Normal Production)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                            <input type="radio" name="machine_action" value="set_maintenance">
                            <span>Keep machine under <strong>"Under Maintenance"</strong> (Awaiting test run)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer;">
                            <input type="radio" name="machine_action" value="no_change">
                            <span>Do not alter current machine status (<?= htmlspecialchars($breakdown['current_machine_status']) ?>)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('breakdowns/view.php?id=' . $id) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Update Breakdown Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
