<?php
require __DIR__ . '/../includes/header.php';

$error = null;
if (!empty($_SESSION['is_admin'])) {
    redirect('admin-dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash FROM admins WHERE email = :email_identifier OR phone = :phone_identifier LIMIT 1');
    $stmt->execute(['email_identifier' => $identifier, 'phone_identifier' => $identifier]);
    $admin = $stmt->fetch();

    $passwordValid = $admin && password_verify($password, $admin['password_hash']);
    if ($passwordValid) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email'] = $admin['email'] ?? null;
        $_SESSION['user_phone'] = preg_replace('/\D+/', '', $admin['phone'] ?? '');
        $_SESSION['is_admin'] = true;
        $_SESSION['logged_in'] = true;
        redirect('admin-dashboard');
    }

    $error = 'Invalid admin email/mobile number or password.';
}
?>

<section class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 p-4">
            <h3 class="mb-3">Admin Login</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Admin email or mobile number</label>
                    <input type="text" name="identifier" class="form-control" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">Login as Admin</button>
            </form>
            <p class="text-center small mt-3 mb-0"><a href="<?= url('forgot-password', ['account_type' => 'admin']) ?>">Forgot password?</a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
