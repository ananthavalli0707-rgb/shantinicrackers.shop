<?php
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/smtp.php';

$accountType = ($_GET['account_type'] ?? 'user') === 'admin' ? 'admin' : 'user';
$table = $accountType === 'admin' ? 'admins' : 'users';
$message = null;
$messageType = 'info';
$resetLink = null;
$mailResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $stmt = $pdo->prepare("SELECT id, email FROM {$table} WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $account = $stmt->fetch();

    if ($account && !empty($account['email'])) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_type VARCHAR(10) NOT NULL,
            account_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_account (account_type, account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE account_type = :account_type AND account_id = :account_id AND used_at IS NULL');
        $stmt->execute(['account_type' => $accountType, 'account_id' => $account['id']]);
        $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (account_type, account_id, token_hash, expires_at) VALUES (:account_type, :account_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
        $stmt->execute([
            'account_type' => $accountType,
            'account_id' => $account['id'],
            'token_hash' => $tokenHash,
        ]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = rtrim($scheme . '://' . $host . $scriptPath, '/\\');

        $resetLink = $baseUrl . '/' . url('reset-password', ['token' => $token]);

        $htmlBody = '
        <div style="font-family: \'DM Sans\', Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 12px; background: #fffaf5;">
            <h2 style="color: #c62828; margin-top: 0;">🎆 Shantini Crackers</h2>
            <h3 style="color: #333;">Password Reset Request</h3>
            <p style="color: #555; line-height: 1.5;">We received a request to reset the password for your Shantini Crackers account (<strong>' . htmlspecialchars($account['email']) . '</strong>).</p>
            <p style="color: #555; line-height: 1.5;">Click the button below to reset your password. This link is valid for 30 minutes:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($resetLink) . '" style="background: #e11d48; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset Password</a>
            </p>
            <p style="color: #777; font-size: 0.9em;">If the button above does not work, copy and paste this URL into your browser:</p>
            <p style="color: #0d47a1; font-size: 0.85em; word-break: break-all;"><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">
            <p style="color: #999; font-size: 0.8em; margin-bottom: 0;">If you did not request a password reset, please ignore this email.</p>
        </div>';

        $mailResult = send_smtp_email($account['email'], 'Reset Your Shantini Crackers Password', $htmlBody, true);

        if ($mailResult['success']) {
            $message = 'A password reset link has been sent to ' . htmlspecialchars($account['email']) . ' via SMTP. Please check your inbox.';
            $messageType = 'success';
        } else {
            $message = 'A reset token was generated. ' . htmlspecialchars($mailResult['message']);
            $messageType = 'warning';
        }
    } else {
        $message = 'If an account with that email address exists, a password reset link has been prepared.';
        $messageType = 'info';
    }
}
?>

<section class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 p-4 rounded-4">
            <div class="text-center mb-3">
                <i class="fa-solid fa-key fs-1 text-danger mb-2"></i>
                <h3 class="fw-bold">Forgot Password</h3>
                <p class="text-muted small">Enter your registered email address to receive a reset link via email.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> shadow-sm rounded-3"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Reset Link
                </button>
            </form>

            <?php if ($resetLink && (!isset($mailResult['success']) || !$mailResult['success'])): ?>
                <div class="alert alert-light border mt-4 mb-0 rounded-3">
                    <p class="small text-muted mb-1 fw-semibold"><i class="fa-solid fa-code me-1"></i>Local Test Direct Link:</p>
                    <a href="<?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>" class="small text-break fw-bold text-danger"><?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="<?= url('login') ?>" class="small text-decoration-none text-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Login</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
