<?php
// ====================================================================
// Add New Machine
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Add New Machine';
$currentPage = 'machines';
$breadcrumbs = [
    'Dashboard' => 'dashboard.php',
    'Machines'  => 'machines/index.php',
    'Add'       => ''
];

$errors = [];
$formData = [
    'machine_name'          => '',
    'machine_type'          => '',
    'department'            => 'Production',
    'location'              => '',
    'manufacturer'          => '',
    'model_number'          => '',
    'installation_date'     => date('Y-m-d'),
    'last_maintenance_date' => '',
    'next_maintenance_date' => '',
    'status'                => 'Working',
    'technician_id'         => ''
];

// Fetch active technicians for dropdown
$techStmt = $pdo->query("SELECT id, name, employee_id, specialization FROM technicians WHERE status = 'Active' ORDER BY name ASC");
$technicians = $techStmt->fetchAll();

$departments = ['Production', 'Packaging', 'Assembly', 'Maintenance', 'Quality Control'];
$statuses = ['Working', 'Under Maintenance', 'Breakdown', 'Inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['machine_name']          = trim($_POST['machine_name'] ?? '');
    $formData['machine_type']          = trim($_POST['machine_type'] ?? '');
    $formData['department']            = trim($_POST['department'] ?? '');
    $formData['location']              = trim($_POST['location'] ?? '');
    $formData['manufacturer']          = trim($_POST['manufacturer'] ?? '');
    $formData['model_number']          = trim($_POST['model_number'] ?? '');
    $formData['installation_date']     = trim($_POST['installation_date'] ?? '');
    $formData['last_maintenance_date'] = !empty($_POST['last_maintenance_date']) ? trim($_POST['last_maintenance_date']) : null;
    $formData['next_maintenance_date'] = !empty($_POST['next_maintenance_date']) ? trim($_POST['next_maintenance_date']) : null;
    $formData['status']                = trim($_POST['status'] ?? 'Working');
    $formData['technician_id']         = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;

    // Validation
    if (empty($formData['machine_name'])) {
        $errors[] = 'Machine Name is required.';
    }
    if (empty($formData['machine_type'])) {
        $errors[] = 'Machine Type is required.';
    }
    if (empty($formData['department']) || !in_array($formData['department'], $departments)) {
        $errors[] = 'Please select a valid department.';
    }
    if (empty($formData['location'])) {
        $errors[] = 'Factory Floor Location is required.';
    }
    if (empty($formData['manufacturer'])) {
        $errors[] = 'Manufacturer name is required.';
    }
    if (empty($formData['model_number'])) {
        $errors[] = 'Model / Serial Number is required.';
    }
    if (empty($formData['installation_date'])) {
        $errors[] = 'Installation date is required.';
    }
    if (!in_array($formData['status'], $statuses)) {
        $errors[] = 'Invalid equipment status selected.';
    }

    if (empty($errors)) {
        try {
            $insertSql = "
                INSERT INTO machines (
                    machine_name, machine_type, department, location, 
                    manufacturer, model_number, installation_date, 
                    last_maintenance_date, next_maintenance_date, status, technician_id
                ) VALUES (
                    :machine_name, :machine_type, :department, :location,
                    :manufacturer, :model_number, :installation_date,
                    :last_maintenance_date, :next_maintenance_date, :status, :technician_id
                )
            ";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':machine_name'          => $formData['machine_name'],
                ':machine_type'          => $formData['machine_type'],
                ':department'            => $formData['department'],
                ':location'              => $formData['location'],
                ':manufacturer'          => $formData['manufacturer'],
                ':model_number'          => $formData['model_number'],
                ':installation_date'     => $formData['installation_date'],
                ':last_maintenance_date' => $formData['last_maintenance_date'],
                ':next_maintenance_date' => $formData['next_maintenance_date'],
                ':status'                => $formData['status'],
                ':technician_id'         => $formData['technician_id']
            ]);

            flash_message('success', "Machine '{$formData['machine_name']}' has been successfully registered!");
            redirect('machines/index.php');
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
            <h2 class="card-title"><i class="fa-solid fa-plus-circle"></i> Register New Industrial Machine</h2>
            <div class="card-subtitle">Add a piece of manufacturing or utility equipment into the tracking system</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('machines/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Machines
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div>
                        <strong>Please correct the following errors:</strong>
                        <ul style="margin: 6px 0 0 20px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= url('machines/add.php') ?>" method="POST">
            <div class="form-grid">
                <!-- Machine Name -->
                <div class="form-group col-full">
                    <label for="machine_name" class="form-label">Machine Name <span class="required">*</span></label>
                    <input type="text" id="machine_name" name="machine_name" class="form-control" placeholder="e.g. CNC Vertical Milling Center VF-2" value="<?= htmlspecialchars($formData['machine_name']) ?>" required>
                    <div class="form-hint">Descriptive identifier used on the factory floor</div>
                </div>

                <!-- Machine Type -->
                <div class="form-group">
                    <label for="machine_type" class="form-label">Machine Type / Category <span class="required">*</span></label>
                    <input type="text" id="machine_type" name="machine_type" class="form-control" placeholder="e.g. CNC Machining, Hydraulic Press" value="<?= htmlspecialchars($formData['machine_type']) ?>" required>
                </div>

                <!-- Department -->
                <div class="form-group">
                    <label for="department" class="form-label">Plant Department <span class="required">*</span></label>
                    <select id="department" name="department" class="form-select" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept ?>" <?= ($formData['department'] === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Factory Location -->
                <div class="form-group">
                    <label for="location" class="form-label">Factory Floor Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Bay 2 - Shop Floor A" value="<?= htmlspecialchars($formData['location']) ?>" required>
                </div>

                <!-- Manufacturer -->
                <div class="form-group">
                    <label for="manufacturer" class="form-label">Manufacturer / OEM <span class="required">*</span></label>
                    <input type="text" id="manufacturer" name="manufacturer" class="form-control" placeholder="e.g. Haas Automation, Schuler" value="<?= htmlspecialchars($formData['manufacturer']) ?>" required>
                </div>

                <!-- Model Number -->
                <div class="form-group">
                    <label for="model_number" class="form-label">Model / Serial Number <span class="required">*</span></label>
                    <input type="text" id="model_number" name="model_number" class="form-control" placeholder="e.g. VF-2SS-2024" value="<?= htmlspecialchars($formData['model_number']) ?>" required>
                </div>

                <!-- Installation Date -->
                <div class="form-group">
                    <label for="installation_date" class="form-label">Commissioning / Installation Date <span class="required">*</span></label>
                    <input type="date" id="installation_date" name="installation_date" class="form-control" value="<?= htmlspecialchars($formData['installation_date']) ?>" required>
                </div>

                <!-- Initial Status -->
                <div class="form-group">
                    <label for="status" class="form-label">Initial Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($formData['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Assigned Technician -->
                <div class="form-group">
                    <label for="technician_id" class="form-label">Assigned Lead Technician</label>
                    <select id="technician_id" name="technician_id" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech['id'] ?>" <?= ($formData['technician_id'] == $tech['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tech['name']) ?> (<?= htmlspecialchars($tech['employee_id']) ?> - <?= htmlspecialchars($tech['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Last Maintenance Date -->
                <div class="form-group">
                    <label for="last_maintenance_date" class="form-label">Last Maintenance Date</label>
                    <input type="date" id="last_maintenance_date" name="last_maintenance_date" class="form-control" value="<?= htmlspecialchars($formData['last_maintenance_date'] ?? '') ?>">
                </div>

                <!-- Next Scheduled Maintenance Date -->
                <div class="form-group">
                    <label for="next_maintenance_date" class="form-label">Next Maintenance Target Date</label>
                    <input type="date" id="next_maintenance_date" name="next_maintenance_date" class="form-control" value="<?= htmlspecialchars($formData['next_maintenance_date'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('machines/index.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Equipment
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
