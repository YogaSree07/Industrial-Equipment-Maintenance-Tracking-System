<?php
// ====================================================================
// Edit Machine Details
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid machine identifier specified.', 'error');
    redirect('machines/index.php');
}

// Fetch existing machine record
$stmt = $pdo->prepare("SELECT * FROM machines WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$machine = $stmt->fetch();

if (!$machine) {
    flash_message('error', 'Machine record not found in system.', 'error');
    redirect('machines/index.php');
}

$pageTitle = 'Edit ' . htmlspecialchars($machine['machine_name']);
$currentPage = 'machines';
$breadcrumbs = [
    'Dashboard' => 'dashboard.php',
    'Machines'  => 'machines/index.php',
    'Edit'      => ''
];

$errors = [];
$departments = ['Production', 'Packaging', 'Assembly', 'Maintenance', 'Quality Control'];
$statuses = ['Working', 'Under Maintenance', 'Breakdown', 'Inactive'];

// Fetch active technicians
$techStmt = $pdo->query("SELECT id, name, employee_id, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC");
$technicians = $techStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine['machine_name']          = trim($_POST['machine_name'] ?? '');
    $machine['machine_type']          = trim($_POST['machine_type'] ?? '');
    $machine['department']            = trim($_POST['department'] ?? '');
    $machine['location']              = trim($_POST['location'] ?? '');
    $machine['manufacturer']          = trim($_POST['manufacturer'] ?? '');
    $machine['model_number']          = trim($_POST['model_number'] ?? '');
    $machine['installation_date']     = trim($_POST['installation_date'] ?? '');
    $machine['last_maintenance_date'] = !empty($_POST['last_maintenance_date']) ? trim($_POST['last_maintenance_date']) : null;
    $machine['next_maintenance_date'] = !empty($_POST['next_maintenance_date']) ? trim($_POST['next_maintenance_date']) : null;
    $machine['status']                = trim($_POST['status'] ?? 'Working');
    $machine['technician_id']         = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;

    if (empty($machine['machine_name'])) {
        $errors[] = 'Machine Name is required.';
    }
    if (empty($machine['machine_type'])) {
        $errors[] = 'Machine Type is required.';
    }
    if (empty($machine['department']) || !in_array($machine['department'], $departments)) {
        $errors[] = 'Please select a valid department.';
    }
    if (empty($machine['location'])) {
        $errors[] = 'Floor Location is required.';
    }
    if (empty($machine['manufacturer'])) {
        $errors[] = 'Manufacturer is required.';
    }
    if (empty($machine['model_number'])) {
        $errors[] = 'Model / Serial Number is required.';
    }
    if (empty($machine['installation_date'])) {
        $errors[] = 'Installation Date is required.';
    }
    if (!in_array($machine['status'], $statuses)) {
        $errors[] = 'Invalid equipment status selected.';
    }

    if (empty($errors)) {
        try {
            $updateSql = "
                UPDATE machines SET
                    machine_name = :machine_name,
                    machine_type = :machine_type,
                    department = :department,
                    location = :location,
                    manufacturer = :manufacturer,
                    model_number = :model_number,
                    installation_date = :installation_date,
                    last_maintenance_date = :last_maintenance_date,
                    next_maintenance_date = :next_maintenance_date,
                    status = :status,
                    technician_id = :technician_id
                WHERE id = :id
            ";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':machine_name'          => $machine['machine_name'],
                ':machine_type'          => $machine['machine_type'],
                ':department'            => $machine['department'],
                ':location'              => $machine['location'],
                ':manufacturer'          => $machine['manufacturer'],
                ':model_number'          => $machine['model_number'],
                ':installation_date'     => $machine['installation_date'],
                ':last_maintenance_date' => $machine['last_maintenance_date'],
                ':next_maintenance_date' => $machine['next_maintenance_date'],
                ':status'                => $machine['status'],
                ':technician_id'         => $machine['technician_id'],
                ':id'                    => $id
            ]);

            flash_message('success', "Equipment '{$machine['machine_name']}' updated successfully.");
            redirect('machines/view.php?id=' . $id);
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Modify Equipment Configuration</h2>
            <div class="card-subtitle">Editing record #<?= $id ?> &mdash; <?= htmlspecialchars($machine['machine_name']) ?></div>
        </div>
        <div class="card-actions">
            <a href="<?= url('machines/view.php?id=' . $id) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-eye"></i> View Profile
            </a>
            <a href="<?= url('machines/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Equipment List
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div>
                        <strong>Please fix the following issues:</strong>
                        <ul style="margin: 6px 0 0 20px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= url('machines/edit.php?id=' . $id) ?>" method="POST">
            <div class="form-grid">
                <div class="form-group col-full">
                    <label for="machine_name" class="form-label">Machine Name <span class="required">*</span></label>
                    <input type="text" id="machine_name" name="machine_name" class="form-control" value="<?= htmlspecialchars($machine['machine_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="machine_type" class="form-label">Machine Type / Category <span class="required">*</span></label>
                    <input type="text" id="machine_type" name="machine_type" class="form-control" value="<?= htmlspecialchars($machine['machine_type']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Plant Department <span class="required">*</span></label>
                    <select id="department" name="department" class="form-select" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept ?>" <?= ($machine['department'] === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location" class="form-label">Factory Floor Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($machine['location']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="manufacturer" class="form-label">Manufacturer / OEM <span class="required">*</span></label>
                    <input type="text" id="manufacturer" name="manufacturer" class="form-control" value="<?= htmlspecialchars($machine['manufacturer']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="model_number" class="form-label">Model / Serial Number <span class="required">*</span></label>
                    <input type="text" id="model_number" name="model_number" class="form-control" value="<?= htmlspecialchars($machine['model_number']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="installation_date" class="form-label">Installation Date <span class="required">*</span></label>
                    <input type="date" id="installation_date" name="installation_date" class="form-control" value="<?= htmlspecialchars($machine['installation_date']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Equipment Operational Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($machine['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="technician_id" class="form-label">Assigned Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech['id'] ?>" <?= ($machine['technician_id'] == $tech['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tech['name']) ?> (<?= htmlspecialchars($tech['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="last_maintenance_date" class="form-label">Last Maintenance Date</label>
                    <input type="date" id="last_maintenance_date" name="last_maintenance_date" class="form-control" value="<?= htmlspecialchars($machine['last_maintenance_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="next_maintenance_date" class="form-label">Next Maintenance Target Date</label>
                    <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control" value="<?= htmlspecialchars($machine['next_maintenance_date'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('machines/view.php?id=' . $id) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Update Equipment
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
