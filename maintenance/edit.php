<?php
// ====================================================================
// Edit Maintenance Record & Status Synchronization
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid maintenance ID.', 'error');
    redirect('maintenance/index.php');
}

// Fetch existing record
$stmt = $pdo->prepare("SELECT * FROM maintenance_records WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$record = $stmt->fetch();

if (!$record) {
    flash_message('error', 'Maintenance record not found.', 'error');
    redirect('maintenance/index.php');
}

$pageTitle = 'Edit Maintenance Log #' . $id;
$currentPage = 'maintenance';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Maintenance' => 'maintenance/index.php',
    'Edit #' . $id => ''
];

// Fetch machines & technicians
$machines = $pdo->query("SELECT id, machine_name, department, location FROM machines ORDER BY machine_name ASC")->fetchAll();
$technicians = $pdo->query("SELECT id, name, employee_id, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$errors = [];
$types = ['Preventive', 'Corrective', 'Emergency'];
$statuses = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record['machine_id']       = (int)($_POST['machine_id'] ?? 0);
    $record['maintenance_type'] = trim($_POST['maintenance_type'] ?? 'Preventive');
    $record['scheduled_date']   = trim($_POST['scheduled_date'] ?? '');
    $record['completed_date']   = !empty($_POST['completed_date']) ? trim($_POST['completed_date']) : null;
    $record['technician_id']    = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    $record['description']      = trim($_POST['description'] ?? '');
    $record['parts_replaced']   = trim($_POST['parts_replaced'] ?? '');
    $record['cost']             = trim($_POST['cost'] ?? '0.00');
    $record['status']           = trim($_POST['status'] ?? 'Scheduled');

    // Validation
    if ($record['machine_id'] <= 0) {
        $errors[] = 'Please select a valid machine.';
    }
    if (!in_array($record['maintenance_type'], $types)) {
        $errors[] = 'Invalid maintenance category.';
    }
    if (empty($record['scheduled_date'])) {
        $errors[] = 'Scheduled date is required.';
    }
    if (empty($record['description'])) {
        $errors[] = 'Maintenance description is required.';
    }
    if (!is_numeric($record['cost']) || (float)$record['cost'] < 0) {
        $errors[] = 'Cost must be a valid positive number.';
    }
    if (!in_array($record['status'], $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Auto-fill completion date if marked completed and date empty
            if ($record['status'] === 'Completed' && empty($record['completed_date'])) {
                $record['completed_date'] = date('Y-m-d');
            }

            $updateSql = "
                UPDATE maintenance_records SET
                    machine_id = :machine_id,
                    maintenance_type = :maintenance_type,
                    scheduled_date = :scheduled_date,
                    completed_date = :completed_date,
                    technician_id = :technician_id,
                    description = :description,
                    parts_replaced = :parts_replaced,
                    cost = :cost,
                    status = :status
                WHERE id = :id
            ";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':machine_id'       => $record['machine_id'],
                ':maintenance_type' => $record['maintenance_type'],
                ':scheduled_date'   => $record['scheduled_date'],
                ':completed_date'   => $record['completed_date'],
                ':technician_id'    => $record['technician_id'],
                ':description'      => $record['description'],
                ':parts_replaced'   => $record['parts_replaced'],
                ':cost'             => $record['cost'],
                ':status'           => $record['status'],
                ':id'               => $id
            ]);

            // Machine state synchronization logic
            if ($record['status'] === 'In Progress') {
                $pdo->prepare("UPDATE machines SET status = 'Under Maintenance' WHERE id = :id")->execute([':id' => $record['machine_id']]);
            } elseif ($record['status'] === 'Completed') {
                $pdo->prepare("
                    UPDATE machines 
                    SET last_maintenance_date = :cdate,
                        status = CASE WHEN status = 'Under Maintenance' THEN 'Working' ELSE status END
                    WHERE id = :id
                ")->execute([
                    ':cdate' => $record['completed_date'],
                    ':id'    => $record['machine_id']
                ]);

                if ($record['maintenance_type'] === 'Preventive') {
                    $nextTarget = date('Y-m-d', strtotime($record['completed_date'] . ' + 90 days'));
                    $pdo->prepare("UPDATE machines SET next_maintenance_date = :nxt WHERE id = :id")->execute([
                        ':nxt' => $nextTarget,
                        ':id'  => $record['machine_id']
                    ]);
                }
            }

            $pdo->commit();
            flash_message('success', "Maintenance log #{$id} updated successfully.");
            redirect('maintenance/view.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Modify Maintenance Order #<?= $id ?></h2>
            <div class="card-subtitle">Update maintenance progress, work log, and parts replaced</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('maintenance/view.php?id=' . $id) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-eye"></i> View Record
            </a>
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

        <form action="<?= url('maintenance/edit.php?id=' . $id) ?>" method="POST">
            <div class="form-grid">
                <div class="form-group col-full">
                    <label for="machine_id" class="form-label">Target Equipment <span class="required">*</span></label>
                    <select id="machine_id" name="machine_id" class="form-select" required>
                        <?php foreach ($machines as $mac): ?>
                            <option value="<?= $mac['id'] ?>" <?= ($record['machine_id'] == $mac['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mac['machine_name']) ?> &mdash; [<?= htmlspecialchars($mac['department']) ?> / <?= htmlspecialchars($mac['location']) ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="maintenance_type" class="form-label">Category <span class="required">*</span></label>
                    <select id="maintenance_type" name="maintenance_type" class="form-select" required>
                        <?php foreach ($types as $tp): ?>
                            <option value="<?= $tp ?>" <?= ($record['maintenance_type'] === $tp) ? 'selected' : '' ?>><?= $tp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Maintenance Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($record['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="scheduled_date" class="form-label">Scheduled Date <span class="required">*</span></label>
                    <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" value="<?= htmlspecialchars($record['scheduled_date']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="completed_date" class="form-label">Completed Date</label>
                    <input type="date" id="completed_date" name="completed_date" class="form-control" value="<?= htmlspecialchars($record['completed_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="technician_id" class="form-label">Assigned Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($technicians as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($record['technician_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cost" class="form-label">Recorded Cost ($) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" id="cost" name="cost" class="form-control" value="<?= htmlspecialchars($record['cost']) ?>" required>
                </div>

                <div class="form-group col-full">
                    <label for="description" class="form-label">Work Description / Inspection Notes <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?= htmlspecialchars($record['description']) ?></textarea>
                </div>

                <div class="form-group col-full">
                    <label for="parts_replaced" class="form-label">Parts & Materials Replaced</label>
                    <input type="text" id="parts_replaced" name="parts_replaced" class="form-control" value="<?= htmlspecialchars($record['parts_replaced'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('maintenance/view.php?id=' . $id) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Update Maintenance Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
