<?php
$isStandalonePage = true;
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

<div class="auth-split-layout min-vh-100 d-flex flex-column flex-lg-row">
    <!-- Left Side: Visual/Branding -->
    <div class="auth-side-image d-none d-lg-flex col-lg-6 flex-column justify-content-center align-items-center position-relative overflow-hidden">
        <img src="<?= asset('uploads/shot1.jpg') ?>" alt="Fireworks" class="position-absolute w-100 h-100 object-fit-cover" style="z-index: 1;">
        <div class="position-absolute w-100 h-100" style="background: linear-gradient(135deg, rgba(122,16,36,0.7) 0%, rgba(0,0,0,0.8) 100%); z-index: 2;"></div>
        
        <div class="text-center px-5 position-relative" style="z-index: 3;">
            <h1 class="display-3 fw-bold mb-4" style="font-family: var(--font-heading); background: linear-gradient(45deg, var(--accent-light), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5));">Shantini Crackers</h1>
            <p class="lead fs-4 text-white opacity-75">Ignite the night with premium quality fireworks for your special celebrations.</p>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="auth-form-container col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-dark position-relative">
        
        <!-- Mobile Background Fallback -->
        <div class="d-block d-lg-none position-absolute w-100 h-100 top-0 start-0">
            <img src="<?= asset('uploads/shot1.jpg') ?>" alt="Fireworks" class="w-100 h-100 object-fit-cover" style="opacity: 0.2;">
        </div>

        <div class="card glass-card border-0 p-4 p-md-5 w-100 position-relative z-1" style="max-width: 500px; border-radius: 20px;">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-white mb-2" style="font-family: var(--font-heading);">Welcome Back</h2>
                <p class="text-white-50">Please enter your details to sign in.</p>
            </div>
            
            <?php if ($error): ?><div class="alert alert-danger shadow-sm border-0 bg-danger text-white bg-opacity-75"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <form method="POST">
                <?= csrf_field() ?>
                
                <div class="mb-4">
                    <label for="identifierInput" class="form-label text-white-50 small mb-1">Email or mobile number</label>
                    <input type="text" name="identifier" class="form-control bg-transparent text-white custom-input" id="identifierInput" placeholder="Enter your email or mobile" autocomplete="username" required>
                </div>
                
                <div class="mb-4">
                    <label for="passwordInput" class="form-label text-white-50 small mb-1">Password</label>
                    <input type="password" name="password" class="form-control bg-transparent text-white custom-input" id="passwordInput" placeholder="Enter your password" autocomplete="current-password" required>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div class="form-check custom-checkbox">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label text-white-50 small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="<?= url('forgot-password', ['account_type' => 'user']) ?>" class="text-accent small text-decoration-none fw-medium">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-accent btn-lg w-100 fw-bold shadow-lg" style="border-radius: 12px; letter-spacing: 0.5px;">Sign In</button>
            </form>
            
            <p class="text-center text-white-50 mt-4 pt-3 border-top border-secondary mb-0">
                New customer? <a href="<?= url('signup') ?>" class="text-white fw-bold text-decoration-none hover-accent">Create an account</a>
            </p>
            <div class="text-center mt-4">
                 <a href="<?= url('home') ?>" class="text-white-50 small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Glassmorphism Auth Utilities */
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
}

.custom-input {
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    padding: 12px 15px;
}

.custom-input:focus {
    box-shadow: none;
    border-color: var(--accent);
    background: rgba(255,255,255,0.05) !important;
    color: white;
}

.custom-input:-webkit-autofill {
    -webkit-text-fill-color: white !important;
    transition: background-color 5000s ease-in-out 0s;
}

.custom-checkbox .form-check-input {
    background-color: transparent;
    border-color: rgba(255,255,255,0.3);
}

.custom-checkbox .form-check-input:checked {
    background-color: var(--accent);
    border-color: var(--accent);
}

.hover-accent:hover {
    color: var(--accent) !important;
}
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>
