<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['logged_in'])) {
    redirect('login');
}
?>

<section>
    <h2 class="fw-bold mb-4">My Account</h2>
    <div class="card shadow-sm border-0 p-4">
        <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?></p>
        <p><strong>Status:</strong> Logged in</p>
        <a href="<?= url('cart') ?>" class="btn btn-danger">View Estimate</a>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
