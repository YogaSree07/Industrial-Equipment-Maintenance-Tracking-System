<?php
// ====================================================================
// Add New Technician
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Register New Technician';
$currentPage = 'technicians';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Technicians' => 'technicians/index.php',
    'Add'         => ''
];

$errors = [];
$formData = [
    'name'           => '',
    'employee_id'    => '',
    'department'     => 'Maintenance',
    'specialization' => '',
    'phone'          => '',
    'email'          => '',
    'joining_date'   => date('Y-m-d'),
    'status'         => 'Active'
];

$departments = ['Production', 'Packaging', 'Assembly', 'Maintenance', 'Quality Control'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']           = trim($_POST['name'] ?? '');
    $formData['employee_id']    = strtoupper(trim($_POST['employee_id'] ?? ''));
    $formData['department']     = trim($_POST['department'] ?? 'Maintenance');
    $formData['specialization'] = trim($_POST['specialization'] ?? '');
    $formData['phone']          = trim($_POST['phone'] ?? '');
    $formData['email']          = strtolower(trim($_POST['email'] ?? ''));
    $formData['joining_date']   = trim($_POST['joining_date'] ?? '');
    $formData['status']         = trim($_POST['status'] ?? 'Active');

    // Validation
    if (empty($formData['name'])) {
        $errors[] = 'Technician full name is required.';
    }
    if (empty($formData['employee_id'])) {
        $errors[] = 'Employee ID is required.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM technicians WHERE employee_id = :eid LIMIT 1");
        $chk->execute([':eid' => $formData['employee_id']]);
        if ($chk->fetch()) {
            $errors[] = "Employee ID '{$formData['employee_id']}' is already assigned to another technician.";
        }
    }

    if (empty($formData['specialization'])) {
        $errors[] = 'Technical specialization / core skill is required.';
    }
    if (empty($formData['phone'])) {
        $errors[] = 'Contact phone number is required.';
    }
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    } else {
        $chk2 = $pdo->prepare("SELECT id FROM technicians WHERE email = :email LIMIT 1");
        $chk2->execute([':email' => $formData['email']]);
        if ($chk2->fetch()) {
            $errors[] = "Email address '{$formData['email']}' is already in use.";
        }
    }
    if (empty($formData['joining_date'])) {
        $errors[] = 'Joining date is required.';
    }

    if (empty($errors)) {
        try {
            $insertSql = "
                INSERT INTO technicians (
                    name, employee_id, department, specialization,
                    phone, email, joining_date, status
                ) VALUES (
                    :name, :employee_id, :department, :specialization,
                    :phone, :email, :joining_date, :status
                )
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':name'           => $formData['name'],
                ':employee_id'    => $formData['employee_id'],
                ':department'     => $formData['department'],
                ':specialization' => $formData['specialization'],
                ':phone'          => $formData['phone'],
                ':email'          => $formData['email'],
                ':joining_date'   => $formData['joining_date'],
                ':status'         => $formData['status']
            ]);

            flash_message('success', "Technician '{$formData['name']}' ({$formData['employee_id']}) registered successfully!");
            redirect('technicians/index.php');
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 850px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-user-plus"></i> Register Factory Technician</h2>
            <div class="card-subtitle">Add a specialized maintenance or service technician to the team</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('technicians/index.php') ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Staff Directory
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

        <form action="<?= url('technicians/add.php') ?>" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Anand R." value="<?= htmlspecialchars($formData['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="employee_id" class="form-label">Employee ID <span class="required">*</span></label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" placeholder="e.g. TECH-108" value="<?= htmlspecialchars($formData['employee_id']) ?>" required>
                    <div class="form-hint">Unique badge / workforce identifier</div>
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Assigned Department <span class="required">*</span></label>
                    <select id="department" name="department" class="form-select" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept ?>" <?= ($formData['department'] === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Employment Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Active" <?= ($formData['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($formData['status'] === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group col-full">
                    <label for="specialization" class="form-label">Primary Technical Specialization <span class="required">*</span></label>
                    <input type="text" id="specialization" name="specialization" class="form-control" placeholder="e.g. Hydraulic Valves, PLC & Servo Drives, High Voltage Switchgear" value="<?= htmlspecialchars($formData['specialization']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. +91 98765 43210" value="<?= htmlspecialchars($formData['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. anand@factory.com" value="<?= htmlspecialchars($formData['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="joining_date" class="form-label">Date of Joining <span class="required">*</span></label>
                    <input type="date" id="joining_date" name="joining_date" class="form-control" value="<?= htmlspecialchars($formData['joining_date']) ?>" required>
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('technicians/index.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Register Technician
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
