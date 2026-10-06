<?php
// ====================================================================
// Add / Schedule Maintenance Record
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Schedule Maintenance';
$currentPage = 'maintenance';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Maintenance' => 'maintenance/index.php',
    'Schedule'    => ''
];

$preselectedMachineId = (int)($_GET['machine_id'] ?? 0);

// Fetch all machines for dropdown
$machines = $pdo->query("SELECT id, machine_name, department, location, technician_id FROM machines ORDER BY machine_name ASC")->fetchAll();

// Fetch active technicians
$technicians = $pdo->query("SELECT id, name, employee_id, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$errors = [];
$formData = [
    'machine_id'       => $preselectedMachineId > 0 ? $preselectedMachineId : '',
    'maintenance_type' => 'Preventive',
    'scheduled_date'   => date('Y-m-d'),
    'completed_date'   => '',
    'technician_id'    => '',
    'description'      => '',
    'parts_replaced'   => '',
    'cost'             => '0.00',
    'status'           => 'Scheduled'
];

// If machine is preselected, default technician to machine's assigned technician
if ($preselectedMachineId > 0) {
    foreach ($machines as $m) {
        if ($m['id'] == $preselectedMachineId && !empty($m['technician_id'])) {
            $formData['technician_id'] = $m['technician_id'];
            break;
        }
    }
}

$types = ['Preventive', 'Corrective', 'Emergency'];
$statuses = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['machine_id']       = (int)($_POST['machine_id'] ?? 0);
    $formData['maintenance_type'] = trim($_POST['maintenance_type'] ?? 'Preventive');
    $formData['scheduled_date']   = trim($_POST['scheduled_date'] ?? '');
    $formData['completed_date']   = !empty($_POST['completed_date']) ? trim($_POST['completed_date']) : null;
    $formData['technician_id']    = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    $formData['description']      = trim($_POST['description'] ?? '');
    $formData['parts_replaced']   = trim($_POST['parts_replaced'] ?? '');
    $formData['cost']             = trim($_POST['cost'] ?? '0.00');
    $formData['status']           = trim($_POST['status'] ?? 'Scheduled');

    // Validation
    if ($formData['machine_id'] <= 0) {
        $errors[] = 'Please select a machine to service.';
    }
    if (!in_array($formData['maintenance_type'], $types)) {
        $errors[] = 'Invalid maintenance type selected.';
    }
    if (empty($formData['scheduled_date'])) {
        $errors[] = 'Scheduled maintenance date is required.';
    }
    if (empty($formData['description'])) {
        $errors[] = 'Maintenance description / scope of work is required.';
    }
    if (!is_numeric($formData['cost']) || (float)$formData['cost'] < 0) {
        $errors[] = 'Cost must be a valid positive number.';
    }
    if (!in_array($formData['status'], $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // If marked completed right away, default completed_date to today if omitted
            if ($formData['status'] === 'Completed' && empty($formData['completed_date'])) {
                $formData['completed_date'] = date('Y-m-d');
            }

            // Insert maintenance record
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_records (
                    machine_id, maintenance_type, scheduled_date, completed_date,
                    technician_id, description, parts_replaced, cost, status
                ) VALUES (
                    :machine_id, :maintenance_type, :scheduled_date, :completed_date,
                    :technician_id, :description, :parts_replaced, :cost, :status
                )
            ");
            $stmt->execute([
                ':machine_id'       => $formData['machine_id'],
                ':maintenance_type' => $formData['maintenance_type'],
                ':scheduled_date'   => $formData['scheduled_date'],
                ':completed_date'   => $formData['completed_date'],
                ':technician_id'    => $formData['technician_id'],
                ':description'      => $formData['description'],
                ':parts_replaced'   => $formData['parts_replaced'],
                ':cost'             => $formData['cost'],
                ':status'           => $formData['status']
            ]);

            // Synchronize machine status and dates
            if ($formData['status'] === 'In Progress') {
                $pdo->prepare("UPDATE machines SET status = 'Under Maintenance' WHERE id = :id")->execute([':id' => $formData['machine_id']]);
            } elseif ($formData['status'] === 'Completed') {
                $pdo->prepare("
                    UPDATE machines 
                    SET last_maintenance_date = :cdate,
                        status = CASE WHEN status = 'Under Maintenance' THEN 'Working' ELSE status END
                    WHERE id = :id
                ")->execute([
                    ':cdate' => $formData['completed_date'],
                    ':id'    => $formData['machine_id']
                ]);

                // If Preventive maintenance, calculate next maintenance in 90 days
                if ($formData['maintenance_type'] === 'Preventive') {
                    $nextDate = date('Y-m-d', strtotime($formData['completed_date'] . ' + 90 days'));
                    $pdo->prepare("UPDATE machines SET next_maintenance_date = :next WHERE id = :id")->execute([
                        ':next' => $nextDate,
                        ':id'   => $formData['machine_id']
                    ]);
                }
            }

            $pdo->commit();
            flash_message('success', 'Maintenance record successfully created!');
            redirect('maintenance/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to save maintenance record: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-calendar-plus"></i> Schedule / Record Maintenance Work</h2>
            <div class="card-subtitle">Define scheduled preventive servicing or log emergency repairs</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('maintenance/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Logs
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div>
                        <strong>Please resolve the following errors:</strong>
                        <ul style="margin: 6px 0 0 20px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= url('maintenance/add.php') ?>" method="POST">
            <div class="form-grid">
                <!-- Machine Selection -->
                <div class="form-group col-full">
                    <label for="machine_id" class="form-label">Industrial Machine to Service <span class="required">*</span></label>
                    <select id="machine_id" name="machine_id" class="form-select" required>
                        <option value="">-- Select Target Equipment --</option>
                        <?php foreach ($machines as $mac): ?>
                            <option value="<?= $mac['id'] ?>" <?= ($formData['machine_id'] == $mac['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mac['machine_name']) ?> &mdash; [<?= htmlspecialchars($mac['department']) ?> / <?= htmlspecialchars($mac['location']) ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Maintenance Type -->
                <div class="form-group">
                    <label for="maintenance_type" class="form-label">Maintenance Category <span class="required">*</span></label>
                    <select id="maintenance_type" name="maintenance_type" class="form-select" required>
                        <?php foreach ($types as $tp): ?>
                            <option value="<?= $tp ?>" <?= ($formData['maintenance_type'] === $tp) ? 'selected' : '' ?>><?= $tp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status" class="form-label">Job Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($formData['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Scheduled Date -->
                <div class="form-group">
                    <label for="scheduled_date" class="form-label">Scheduled Date <span class="required">*</span></label>
                    <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" value="<?= htmlspecialchars($formData['scheduled_date']) ?>" required>
                </div>

                <!-- Completed Date -->
                <div class="form-group">
                    <label for="completed_date" class="form-label">Completion Date (If finished)</label>
                    <input type="date" id="completed_date" name="completed_date" class="form-control" value="<?= htmlspecialchars($formData['completed_date'] ?? '') ?>">
                </div>

                <!-- Technician -->
                <div class="form-group">
                    <label for="technician_id" class="form-label">Assigned Maintenance Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($formData['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['employee_id']) ?> - <?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cost -->
                <div class="form-group">
                    <label for="cost" class="form-label">Service & Parts Cost ($) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" id="cost" name="cost" class="form-control" value="<?= htmlspecialchars($formData['cost']) ?>" required>
                </div>

                <!-- Description -->
                <div class="form-group col-full">
                    <label for="description" class="form-label">Maintenance Scope / Work Order Details <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe the maintenance procedure, checks performed, lubricants replaced..." required><?= htmlspecialchars($formData['description']) ?></textarea>
                </div>

                <!-- Parts Replaced -->
                <div class="form-group col-full">
                    <label for="parts_replaced" class="form-label">Replacement Parts / Consumables Used</label>
                    <input type="text" id="parts_replaced" name="parts_replaced" class="form-control" placeholder="e.g. Oil filter, pneumatic seals, O-rings, 5L hydraulic oil" value="<?= htmlspecialchars($formData['parts_replaced']) ?>">
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('maintenance/index.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Maintenance Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
