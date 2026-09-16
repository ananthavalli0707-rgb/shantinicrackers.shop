<?php
$isStandalonePage = true;
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

<div class="auth-split-layout min-vh-100 d-flex flex-column flex-lg-row">
    <!-- Left Side: Visual/Branding -->
    <div class="auth-side-image d-none d-lg-flex col-lg-6 flex-column justify-content-center align-items-center position-relative overflow-hidden">
        <img src="<?= asset('uploads/shot1.jpg') ?>" alt="Fireworks" class="position-absolute w-100 h-100 object-fit-cover" style="z-index: 1; filter: grayscale(50%);">
        <div class="position-absolute w-100 h-100" style="background: linear-gradient(135deg, rgba(30,30,30,0.8) 0%, rgba(0,0,0,0.9) 100%); z-index: 2;"></div>
        
        <div class="text-center px-5 position-relative" style="z-index: 3;">
            <h1 class="display-3 fw-bold mb-4" style="font-family: var(--font-heading); color: #fff; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5));">Admin Portal</h1>
            <p class="lead fs-4 text-white opacity-75">Shantini Crackers Secure Management System</p>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="auth-form-container col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-dark position-relative">
        
        <!-- Mobile Background Fallback -->
        <div class="d-block d-lg-none position-absolute w-100 h-100 top-0 start-0">
            <img src="<?= asset('uploads/shot1.jpg') ?>" alt="Fireworks" class="w-100 h-100 object-fit-cover" style="opacity: 0.15; filter: grayscale(100%);">
        </div>

        <div class="card glass-card border-0 p-4 p-md-5 w-100 position-relative z-1" style="max-width: 500px; border-radius: 20px;">
            <div class="text-center mb-5">
                <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-10 rounded-circle mb-3" style="width: 70px; height: 70px;">
                    <i class="fa-solid fa-shield-halved fs-2 text-white"></i>
                </div>
                <h2 class="fw-bold text-white mb-2" style="font-family: var(--font-heading);">Admin Login</h2>
                <p class="text-white-50">Authorized personnel only.</p>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger shadow-sm border-0 bg-danger text-white bg-opacity-75"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <form method="POST">
                <?= csrf_field() ?>
                
                <div class="mb-4">
                    <label class="form-label text-white-50 small mb-1">Admin Email or Mobile</label>
                    <input type="text" name="identifier" class="form-control bg-transparent text-white custom-input" placeholder="Enter admin credentials" autocomplete="username" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-white-50 small mb-1">Admin Password</label>
                    <input type="password" name="password" class="form-control bg-transparent text-white custom-input" placeholder="Enter password" autocomplete="current-password" required>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div class="form-check custom-checkbox">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label text-white-50 small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="<?= url('forgot-password', ['account_type' => 'admin']) ?>" class="text-white-50 small text-decoration-none">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-light btn-lg w-100 fw-bold shadow-lg" style="border-radius: 12px; letter-spacing: 0.5px;">Authenticate</button>
            </form>
            
            <div class="text-center mt-4 pt-3 border-top border-secondary">
                 <a href="<?= url('login') ?>" class="text-white-50 small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Return to Customer Login</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
