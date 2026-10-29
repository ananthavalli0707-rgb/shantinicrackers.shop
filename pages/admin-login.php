<?php
$isStandalonePage = true;
require __DIR__ . '/../includes/header.php';

$error = null;
if (!empty($_SESSION['is_admin'])) {
    redirect('admin-analytics');
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
        redirect('admin-analytics');
    }

    $error = 'Invalid admin email/mobile number or password.';
}
?>

<div class="auth-split-layout min-vh-100 d-flex flex-column flex-lg-row">
    <!-- Left Side: Visual/Branding -->
    <div class="auth-side-image d-none d-lg-flex col-lg-6 flex-column justify-content-center align-items-center position-relative overflow-hidden">
        <img src="<?= asset('uploads/shot1.jpg') ?>" alt="Fireworks" class="position-absolute w-100 h-100 object-fit-cover" style="z-index: 1; filter: grayscale(50%);">
        <div class="position-absolute w-100 h-100" style="background: linear-gradient(135deg, rgba(30,30,30,0.85) 0%, rgba(0,0,0,0.95) 100%); z-index: 2;"></div>
        
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
                    <label class="form-label text-white small mb-1 fw-semibold">Admin Email or Mobile</label>
                    <div class="input-group">
                        <span class="input-group-text auth-input-icon">
                            <i class="fa-solid fa-user text-white-50"></i>
                        </span>
                        <input type="text" name="identifier" id="adminIdentifier" class="form-control custom-input border-start-0" placeholder="Enter email or 10-digit mobile" autocomplete="username" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-white small mb-1 fw-semibold">Admin Password</label>
                    <div class="input-group">
                        <span class="input-group-text auth-input-icon">
                            <i class="fa-solid fa-lock text-white-50"></i>
                        </span>
                        <input type="password" name="password" id="adminPassword" class="form-control custom-input border-start-0 border-end-0" placeholder="Enter admin password" autocomplete="current-password" required>
                        <button class="btn auth-toggle-btn" type="button" id="togglePasswordBtn" title="Toggle password visibility">
                            <i class="fa-solid fa-eye text-white-50" id="togglePasswordIcon"></i>
                        </button>
                    </div>
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

<style>
/* Dedicated Glassmorphism & High-Contrast Input Styles for Admin Login */
.glass-card {
    background: rgba(18, 18, 22, 0.94) !important;
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75) !important;
}

.auth-input-icon {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-right: none !important;
    border-top-left-radius: 10px !important;
    border-bottom-left-radius: 10px !important;
    color: #ffffff !important;
}

.custom-input {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    caret-color: #ffffff !important;
    padding: 12px 15px !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
}

.custom-input:focus {
    background-color: rgba(255, 255, 255, 0.15) !important;
    border-color: #D4A72C !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 0.25rem rgba(212, 167, 44, 0.25) !important;
}

.custom-input::placeholder {
    color: rgba(255, 255, 255, 0.45) !important;
    opacity: 1 !important;
}

.auth-toggle-btn {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-left: none !important;
    border-top-right-radius: 10px !important;
    border-bottom-right-radius: 10px !important;
    color: rgba(255, 255, 255, 0.7) !important;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.auth-toggle-btn:hover {
    background-color: rgba(255, 255, 255, 0.18) !important;
    color: #ffffff !important;
}

/* Chrome/Safari/Edge Autofill Fix */
.custom-input:-webkit-autofill,
.custom-input:-webkit-autofill:hover, 
.custom-input:-webkit-autofill:focus, 
.custom-input:-webkit-autofill:active {
    -webkit-text-fill-color: #ffffff !important;
    -webkit-box-shadow: 0 0 0 1000px #1e1e24 inset !important;
    box-shadow: 0 0 0 1000px #1e1e24 inset !important;
    transition: background-color 5000s ease-in-out 0s;
}

.custom-checkbox .form-check-input {
    background-color: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.35);
}

.custom-checkbox .form-check-input:checked {
    background-color: var(--accent);
    border-color: var(--accent);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('adminPassword');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
