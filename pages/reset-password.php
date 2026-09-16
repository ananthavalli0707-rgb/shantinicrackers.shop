<?php
$isStandalonePage = true;
require __DIR__ . '/../includes/header.php';

$token = trim($_GET['token'] ?? '');
$error = null;
$reset = null;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT id, account_type, account_id FROM password_reset_tokens WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute(['token_hash' => hash('sha256', $token)]);
    $reset = $stmt->fetch();
}

if (!$reset) {
    $error = 'This password reset link is invalid or has expired. Please request a new link.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Your new password must be at least 8 characters long.';
    } elseif ($password !== $confirmation) {
        $error = 'The passwords do not match. Please ensure both fields match.';
    } else {
        $table = $reset['account_type'] === 'admin' ? 'admins' : 'users';
        $stmt = $pdo->prepare("UPDATE {$table} SET password_hash = :password_hash WHERE id = :id");
        $stmt->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $reset['account_id']
        ]);

        $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $reset['id']]);

        $_SESSION['login_flash'] = 'Your password has been successfully updated! You can now log in with your new password.';
        redirect($reset['account_type'] === 'admin' ? 'admin-login' : 'login');
    }
}
?>

<section class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 p-4 rounded-4">
            <div class="text-center mb-3">
                <i class="fa-solid fa-lock fs-1 text-danger mb-2"></i>
                <h3 class="fw-bold">Set New Password</h3>
                <p class="text-muted small">Please enter your new password and confirm it below.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm rounded-3">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($reset): ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-key"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="Minimum 8 characters" minlength="8" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-check-double"></i></span>
                            <input type="password" name="confirm_password" class="form-control border-start-0" placeholder="Re-enter new password" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm mt-2">
                        <i class="fa-solid fa-shield-halved me-1"></i> Update Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center mt-3">
                    <a href="<?= url('forgot-password') ?>" class="btn btn-outline-danger fw-bold rounded-pill px-4">
                        Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="<?= url('login') ?>" class="small text-decoration-none text-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
