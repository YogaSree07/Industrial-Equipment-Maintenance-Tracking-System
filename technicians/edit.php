<?php
// ====================================================================
// Edit Technician Profile
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_message('error', 'Invalid technician ID specified.', 'error');
    redirect('technicians/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$technician = $stmt->fetch();

if (!$technician) {
    flash_message('error', 'Technician record not found.', 'error');
    redirect('technicians/index.php');
}

$pageTitle = 'Edit ' . htmlspecialchars($technician['name']);
$currentPage = 'technicians';
$breadcrumbs = [
    'Dashboard'   => 'dashboard.php',
    'Technicians' => 'technicians/index.php',
    'Edit'        => ''
];

$errors = [];
$departments = ['Production', 'Packaging', 'Assembly', 'Maintenance', 'Quality Control'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $technician['name']           = trim($_POST['name'] ?? '');
    $technician['employee_id']    = strtoupper(trim($_POST['employee_id'] ?? ''));
    $technician['department']     = trim($_POST['department'] ?? 'Maintenance');
    $technician['specialization'] = trim($_POST['specialization'] ?? '');
    $technician['phone']          = trim($_POST['phone'] ?? '');
    $technician['email']          = strtolower(trim($_POST['email'] ?? ''));
    $technician['joining_date']   = trim($_POST['joining_date'] ?? '');
    $technician['status']         = trim($_POST['status'] ?? 'Active');

    if (empty($technician['name'])) {
        $errors[] = 'Full name is required.';
    }
    if (empty($technician['employee_id'])) {
        $errors[] = 'Employee ID is required.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM technicians WHERE employee_id = :eid AND id != :id LIMIT 1");
        $chk->execute([':eid' => $technician['employee_id'], ':id' => $id]);
        if ($chk->fetch()) {
            $errors[] = "Employee ID '{$technician['employee_id']}' is already in use by another technician.";
        }
    }

    if (empty($technician['specialization'])) {
        $errors[] = 'Specialization is required.';
    }
    if (empty($technician['phone'])) {
        $errors[] = 'Phone number is required.';
    }
    if (empty($technician['email']) || !filter_var($technician['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    } else {
        $chk2 = $pdo->prepare("SELECT id FROM technicians WHERE email = :email AND id != :id LIMIT 1");
        $chk2->execute([':email' => $technician['email'], ':id' => $id]);
        if ($chk2->fetch()) {
            $errors[] = "Email address '{$technician['email']}' is already in use by another technician.";
        }
    }
    if (empty($technician['joining_date'])) {
        $errors[] = 'Joining date is required.';
    }

    if (empty($errors)) {
        try {
            $updateSql = "
                UPDATE technicians SET
                    name = :name,
                    employee_id = :employee_id,
                    department = :department,
                    specialization = :specialization,
                    phone = :phone,
                    email = :email,
                    joining_date = :joining_date,
                    status = :status
                WHERE id = :id
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':name'           => $technician['name'],
                ':employee_id'    => $technician['employee_id'],
                ':department'     => $technician['department'],
                ':specialization' => $technician['specialization'],
                ':phone'          => $technician['phone'],
                ':email'          => $technician['email'],
                ':joining_date'   => $technician['joining_date'],
                ':status'         => $technician['status'],
                ':id'             => $id
            ]);

            flash_message('success', "Technician '{$technician['name']}' profile updated successfully.");
            redirect('technicians/view.php?id=' . $id);
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
            <h2 class="card-title"><i class="fa-solid fa-user-pen"></i> Edit Technician Profile</h2>
            <div class="card-subtitle">Modify records for <?= htmlspecialchars($technician['name']) ?> (<?= htmlspecialchars($technician['employee_id']) ?>)</div>
        </div>
        <div class="card-actions">
            <a href="<?= url('technicians/view.php?id=' . $id) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-eye"></i> View Profile
            </a>
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

        <form action="<?= url('technicians/edit.php?id=' . $id) ?>" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($technician['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="employee_id" class="form-label">Employee ID <span class="required">*</span></label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?= htmlspecialchars($technician['employee_id']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Assigned Department <span class="required">*</span></label>
                    <select id="department" name="department" class="form-select" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept ?>" <?= ($technician['department'] === $dept) ? 'selected' : '' ?>><?= $dept ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Employment Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Active" <?= ($technician['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($technician['status'] === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group col-full">
                    <label for="specialization" class="form-label">Primary Technical Specialization <span class="required">*</span></label>
                    <input type="text" id="specialization" name="specialization" class="form-control" value="<?= htmlspecialchars($technician['specialization']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($technician['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($technician['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="joining_date" class="form-label">Date of Joining <span class="required">*</span></label>
                    <input type="date" id="joining_date" name="joining_date" class="form-control" value="<?= htmlspecialchars($technician['joining_date']) ?>" required>
                </div>
            </div>

            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= url('technicians/view.php?id=' . $id) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
