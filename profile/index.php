<?php
// ====================================================================
// Administrator Profile & Security Settings
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$pageTitle = 'Operator Profile & Settings';
$currentPage = 'profile';
$breadcrumbs = ['Dashboard' => 'dashboard.php', 'Profile' => ''];

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    flash_message('error', 'User profile could not be loaded.', 'error');
    redirect('dashboard.php');
}

$profileErrors = [];
$passwordErrors = [];

// Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($name)) {
        $profileErrors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors[] = 'A valid email address is required.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $chk->execute([':email' => $email, ':id' => $userId]);
        if ($chk->fetch()) {
            $profileErrors[] = 'That email address is already registered to another user.';
        }
    }

    if (empty($profileErrors)) {
        try {
            $upStmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            $upStmt->execute([':name' => $name, ':email' => $email, ':id' => $userId]);

            // Update session state
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $currentUser['name'] = $name;
            $currentUser['email'] = $email;

            flash_message('success', 'Profile information updated successfully!');
            redirect('profile/index.php');
        } catch (Exception $e) {
            $profileErrors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Password Change Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPass)) {
        $passwordErrors[] = 'Current password is required.';
    } elseif (!password_verify($currentPass, $currentUser['password']) && $currentUser['password'] !== $currentPass) {
        $passwordErrors[] = 'Current password is incorrect.';
    }

    if (empty($newPass) || strlen($newPass) < 6) {
        $passwordErrors[] = 'New password must be at least 6 characters long.';
    }
    if ($newPass !== $confirmPass) {
        $passwordErrors[] = 'New password confirmation does not match.';
    }

    if (empty($passwordErrors)) {
        try {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $upPassStmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $upPassStmt->execute([':pass' => $newHash, ':id' => $userId]);

            flash_message('success', 'Password changed successfully! Please remember your new credentials.');
            redirect('profile/index.php');
        } catch (Exception $e) {
            $passwordErrors[] = 'Failed to update password: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; max-width: 1000px; margin: 0 auto;">
    <!-- Profile Card & Information -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-user-circle"></i> Operator Profile</h2>
            <span class="badge badge-primary"><?= htmlspecialchars($currentUser['role']) ?></span>
        </div>
        <div class="card-body">
            <?php if (!empty($profileErrors)): ?>
                <div class="alert alert-danger">
                    <div class="alert-content">
                        <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                        <div>
                            <?php foreach ($profileErrors as $err): ?>
                                <div><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700;">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($currentUser['name']) ?></h3>
                    <div style="color: var(--text-muted); font-size: 13px;"><?= htmlspecialchars($currentUser['email']) ?></div>
                    <div style="font-size: 11.5px; color: var(--text-light); margin-top: 4px;">
                        Member since: <?= format_date($currentUser['created_at']) ?>
                    </div>
                </div>
            </div>

            <form action="<?= url('profile/index.php') ?>" method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($currentUser['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Assigned System Role</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['role']) ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                    <div class="form-hint">System roles are managed by factory administrator</div>
                </div>

                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Update Profile Info
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security & Password Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-lock"></i> Security & Password</h2>
            <span class="badge badge-secondary">Bcrypt Encrypted</span>
        </div>
        <div class="card-body">
            <?php if (!empty($passwordErrors)): ?>
                <div class="alert alert-danger">
                    <div class="alert-content">
                        <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                        <div>
                            <?php foreach ($passwordErrors as $err): ?>
                                <div><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?= url('profile/index.php') ?>" method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password <span class="required">*</span></label>
                    <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">New Password <span class="required">*</span></label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
                </div>

                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
