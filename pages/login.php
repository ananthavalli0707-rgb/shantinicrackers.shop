<?php
require __DIR__ . '/../includes/header.php';

$error = null;
if (!empty($_SESSION['logged_in'])) {
    redirect('home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash FROM users WHERE email = :email_identifier OR phone = :phone_identifier LIMIT 1');
    $stmt->execute(['email_identifier' => $identifier, 'phone_identifier' => $identifier]);
    $user = $stmt->fetch();

    $passwordValid = $user && password_verify($password, $user['password_hash']);
    if ($passwordValid) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = preg_replace('/\D+/', '', $user['phone']);
        $_SESSION['logged_in'] = true;
        redirect('home');
    }

    $error = 'Invalid email/mobile number or password.';
}
?>

<section class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 p-4">
            <h3 class="mb-3">Login</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email or mobile number</label>
                    <input type="text" name="identifier" class="form-control" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-danger w-100">Login</button>
            </form>
            <p class="text-center small mt-3 mb-0"><a href="<?= url('forgot-password', ['account_type' => 'user']) ?>">Forgot password?</a></p>
            <p class="text-center text-muted small mt-3 mb-0">New customer? <a href="<?= url('signup') ?>" class="fw-bold text-danger">Create an account</a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
