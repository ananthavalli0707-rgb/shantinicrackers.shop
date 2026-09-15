<?php
require __DIR__ . '/../includes/header.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $phone === '' || strlen($password) < 6) {
        $error = 'Name, phone, and a password of at least 6 characters are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone OR (:email_check <> "" AND email = :email) LIMIT 1');
        $stmt->execute(['phone' => $phone, 'email_check' => $email, 'email' => $email]);
        if ($stmt->fetch()) {
            $error = 'An account already exists for that email or phone number.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (:name, :email, :password_hash, :phone)');
            $stmt->execute([
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
            ]);
            redirect('login');
        }
    }
}
?>

<section class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 p-4">
            <h3 class="mb-3">Sign Up</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" autocomplete="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-muted small">(Optional)</span></label>
                    <input type="email" name="email" class="form-control" autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" inputmode="numeric" autocomplete="tel" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-danger w-100">Create Account</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
