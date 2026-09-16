<?php
$isStandalonePage = true;
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/smtp.php';

$error = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $action = $_POST['action'] ?? 'send_otp';
    
    if ($action === 'send_otp') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $phone === '' || strlen($password) < 6) {
            $error = 'Name, email, phone, and a password of at least 6 characters are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone OR (:email_check <> "" AND email = :email) LIMIT 1');
            $stmt->execute(['phone' => $phone, 'email_check' => $email, 'email' => $email]);
            if ($stmt->fetch()) {
                $error = 'An account already exists for that email or phone number.';
            } else {
                if ($email !== '') {
                    // Generate OTP and send email
                    $otp = random_int(100000, 999999);
                    
                    $message = "Hello {$name},\n\nYour OTP for registering at Shantini Crackers is: {$otp}\n\nThis OTP will expire in 10 minutes.";
                    $mailResult = send_smtp_email($email, "Shantini Crackers - Verify your Email", $message, false);
                    
                    if ($mailResult['success']) {
                        $_SESSION['pending_signup'] = [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'otp' => (string)$otp,
                            'expires_at' => time() + 600 // 10 mins
                        ];
                        $successMessage = "An OTP has been sent to {$email}. Please check your inbox.";
                    } else {
                        $error = "Failed to send OTP to your email. Please try again or skip email. Error: " . $mailResult['message'];
                    }
                } else {
                    // No email provided, register immediately
                    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (:name, :email, :password_hash, :phone)');
                    $stmt->execute([
                        'name' => $name,
                        'email' => null,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'phone' => $phone,
                    ]);
                    redirect('login');
                }
            }
        }
    } elseif ($action === 'verify_otp') {
        $submittedOtp = trim($_POST['otp'] ?? '');
        $pending = $_SESSION['pending_signup'] ?? null;
        
        if (!$pending) {
            $error = 'Your signup session expired. Please start over.';
        } elseif (time() > $pending['expires_at']) {
            $error = 'OTP has expired. Please sign up again.';
            unset($_SESSION['pending_signup']);
        } elseif ($submittedOtp !== $pending['otp']) {
            $error = 'Invalid OTP. Please try again.';
        } else {
            // Success
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, phone) VALUES (:name, :email, :password_hash, :phone)');
            $stmt->execute([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password_hash' => $pending['password_hash'],
                'phone' => $pending['phone'],
            ]);
            unset($_SESSION['pending_signup']);
            redirect('login');
        }
    } elseif ($action === 'cancel') {
        unset($_SESSION['pending_signup']);
        $error = 'Signup cancelled. You can try again.';
    }
}

$showOtpForm = isset($_SESSION['pending_signup']);
?>

<section class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 p-4">
            <h3 class="mb-3">Sign Up</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
            
            <?php if ($showOtpForm): ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="verify_otp">
                    <p class="text-muted mb-4">Please enter the 6-digit OTP sent to <strong><?= htmlspecialchars($_SESSION['pending_signup']['email']) ?></strong>.</p>
                    
                    <div class="mb-4">
                        <label class="form-label">Enter OTP</label>
                        <input type="text" name="otp" class="form-control form-control-lg text-center" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required style="letter-spacing: 5px; font-size: 1.5rem;">
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 mb-3">Verify & Register</button>
                </form>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-outline-danger w-100">Cancel & Start Over</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send_otp">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" autocomplete="email" required>
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
                <p class="text-center small mt-3 mb-0">Already have an account? <a href="<?= url('login') ?>">Login here</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
