<?php
// ====================================================================
// User Login & Session Authentication
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errorMessage = '';
$emailInput = '';

// Handle Logout feedback parameter
if (isset($_GET['logout'])) {
    flash_message('success', 'You have been successfully logged out.', 'info');
}

// Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $emailInput = $email;

    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both your email address and password.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $passwordValid = false;

                // Standard bcrypt verification
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } 
                // Fallback for plain-text password and auto-upgrade to secure bcrypt hash
                elseif ($user['password'] === $password) {
                    $passwordValid = true;
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    $updateStmt->execute([':pass' => $newHash, ':id' => $user['id']]);
                }

                if ($passwordValid) {
                    // Prevent session fixation attack
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user'] = [
                        'id'    => $user['id'],
                        'name'  => $user['name'],
                        'email' => $user['email'],
                        'role'  => $user['role']
                    ];

                    flash_message('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                    $redirectUrl = $_SESSION['redirect_after_login'] ?? url('dashboard.php');
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirectUrl);
                    exit;
                } else {
                    $errorMessage = 'Incorrect password. Please try again.';
                }
            } else {
                $errorMessage = 'No user account found with that email address.';
            }
        } catch (Exception $e) {
            $errorMessage = 'An unexpected authentication error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_FULL_NAME ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="login-page">

<div class="login-card">
    <div class="login-header">
        <div class="login-logo">
            <i class="fa-solid fa-gears"></i>
        </div>
        <h2 class="login-title">EquipTrack Pro</h2>
        <p class="login-subtitle">Industrial Equipment Maintenance & Tracking</p>
    </div>

    <!-- Feedback messages -->
    <?= flash_message('login_error') ?>
    <?= flash_message('success') ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <div class="alert-content">
                <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                <span><?= htmlspecialchars($errorMessage) ?></span>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove();">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="<?= url('login.php') ?>" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="email" class="form-label">Email Address <span class="required">*</span></label>
            <div class="search-box" style="min-width: 100%;">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" id="email" name="email" class="form-control" placeholder="admin@factory.com" value="<?= htmlspecialchars($emailInput) ?>" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password <span class="required">*</span></label>
            <div class="search-box" style="min-width: 100%;">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Dashboard
            </button>
        </div>
    </form>

    <!-- Demo Account Information & Quick Fill Helper -->
    <div class="demo-account-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <strong style="color: #0f172a;"><i class="fa-solid fa-shield-halved"></i> Demo Login Credentials:</strong>
            <button type="button" id="fillDemoBtn" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 11px;">
                Fill Demo
            </button>
        </div>
        <div>Email: <code>admin@factory.com</code></div>
        <div style="margin-top: 2px;">Password: <code>Admin@123</code></div>
    </div>
</div>

<script>
document.getElementById('fillDemoBtn')?.addEventListener('click', function() {
    document.getElementById('email').value = 'admin@factory.com';
    document.getElementById('password').value = 'Admin@123';
});
</script>

</body>
</html>
