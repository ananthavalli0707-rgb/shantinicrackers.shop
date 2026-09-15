<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}
ob_start();

// Prevent caching to ensure back button doesn't show authenticated pages after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../config/db.php';

function redirect($page, array $params = []) {
    header('Location: ' . url($page, $params));
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Your form session has expired. Please go back and try again.');
    }
}

function asset($path) {
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $path = ltrim($path, '/');
    while (str_starts_with($path, 'uploads/uploads/')) {
        $path = substr($path, 8);
    }
    if (str_starts_with($path, 'uploads/')) {
        return $baseDir . '/templates/static/' . $path;
    }
    return $baseDir . '/assets/' . $path;
}

function url($page, $params = []) {
    $query = http_build_query($params);
    $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($page === 'home' && empty($params)) {
        return $baseDir . '/';
    }
    return $baseDir . '/' . urlencode($page) . ($query ? '?' . $query : '');
}

$currentPage = $_GET['page'] ?? 'home';
$isAdminArea = !empty($_SESSION['is_admin']) || str_starts_with($currentPage, 'admin-');
$menuCategories = [];
if (!$isAdminArea) {
    $menuCategories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}

$cartCount = 0;
$cartAmount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int)$qty;
    }
    if (!$isAdminArea) {
        $productIds = array_values(array_filter(array_map('intval', array_keys($_SESSION['cart']))));
        if ($productIds) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $cartStmt = $pdo->prepare("SELECT id, discount_price FROM products WHERE id IN ({$placeholders})");
            $cartStmt->execute($productIds);
            foreach ($cartStmt->fetchAll() as $cartProduct) {
                $cartAmount += (float)$cartProduct['discount_price'] * (int)($_SESSION['cart'][(int)$cartProduct['id']] ?? 0);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shantini Crackers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body data-theme="light">
    <?php if (!$isAdminArea): ?>
        <div class="marquee-banner">
            <div class="container">
                <marquee behavior="scroll" direction="left" scrollamount="6">
                    <span>As per the Supreme Court order, online sale of firecrackers is not permitted. Don't worry, request a quote with us and get the details of our crackers. Please add and submit your enquiry. Minimum order: Rs 3000.</span>
                </marquee>
            </div>
        </div>
    <?php endif; ?>

    <nav class="navbar navbar-dark navbar-expand-lg site-nav">
        <div class="container">
            <a class="navbar-brand fw-bold order-1 order-lg-1" href="<?= url('home') ?>">🎆 Shantini Crackers</a>
            <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0 order-lg-2">
                <button id="themeToggle" class="theme-toggle-btn" type="button" aria-label="Toggle theme">
                    <span class="theme-icon" aria-hidden="true">🌙</span>
                </button>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse order-3 order-lg-1" id="mainNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center">
                    <a class="nav-link" href="<?= url('home') ?>">Home</a>
                    <?php if (!$isAdminArea): ?>
                        <a class="nav-link" href="<?= url('products') ?>">Products</a>
                        <a class="nav-link" href="<?= asset('uploads/price-list.pdf') ?>" target="_blank">Price List</a>
                        <a class="nav-link" href="<?= url('cart') ?>"><i class="fa-solid fa-cart-shopping me-1" aria-hidden="true"></i> Estimate (<?= number_format($cartAmount, 2) ?>)</a>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['logged_in'])): ?>
                        <span class="nav-link nav-user">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <a class="nav-link" href="<?= url('admin-dashboard') ?>">Admin Dashboard</a>
                        <?php endif; ?>
                        <form method="POST" action="<?= url('logout') ?>" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="nav-link btn btn-link p-0 border-0">Logout</button></form>
                    <?php else: ?>
                        <a class="nav-link" href="<?= url('login') ?>">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php if (!$isAdminArea): ?>
        <div class="page-action-links" aria-label="Social and contact links">
            <a href="https://www.facebook.com/share/1AGTaToTGZ/" target="_blank" rel="noopener" class="page-social-link page-facebook" aria-label="Follow us on Facebook" title="Follow us on Facebook">
                <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
            </a>
            <a href="https://www.instagram.com/shantinicrackersivakasi/" target="_blank" rel="noopener" class="page-social-link page-instagram" aria-label="Follow us on Instagram" title="Follow us on Instagram">
                <i class="fa-brands fa-instagram" aria-hidden="true"></i>
            </a>
            <a href="https://wa.me/917708971956?text=Hello%20Shantini%20Crackers%2C%20I%20need%20help%20with%20my%20fireworks%20order." target="_blank" rel="noopener" class="page-social-link page-whatsapp" aria-label="Chat with us on WhatsApp" title="Chat with us on WhatsApp">
                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
            </a>
            <div class="page-contact">
                <div id="contactNumberBox" class="collapse">
                    <div class="card shadow-sm border-0 mb-2">
                        <div class="card-body p-3">
                            <div class="small text-muted mb-1">Contact</div>
                            <div class="fw-bold theme-text">9786361678</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-success btn-lg shadow-sm rounded-circle" data-bs-toggle="collapse" data-bs-target="#contactNumberBox" aria-expanded="false" aria-controls="contactNumberBox" aria-label="Show contact number" title="Show contact number">
                    <i class="fa-solid fa-phone contact-icon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <main class="<?= $isAdminArea ? 'container-fluid' : 'container' ?> page-shell">
        <?php if ($isAdminArea): ?>
            <div class="row admin-layout-row">
                <aside class="col-md-3 col-lg-2 bg-dark text-white admin-sidebar p-3">
                    <h5 class="mb-4 d-none d-md-block">Admin Menu</h5>
                    <ul class="nav nav-pills flex-column mb-auto gap-2">
                        <li class="nav-item">
                            <a href="<?= url('admin-dashboard') ?>" class="nav-link <?= str_starts_with($currentPage, 'admin-dashboard') || str_starts_with($currentPage, 'admin-add-product') || str_starts_with($currentPage, 'admin-edit-product') ? 'active bg-danger' : 'text-white' ?>">
                                <i class="fa-solid fa-box me-2"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('admin-categories') ?>" class="nav-link <?= str_starts_with($currentPage, 'admin-categories') || str_starts_with($currentPage, 'admin-add-category') || str_starts_with($currentPage, 'admin-edit-category') ? 'active bg-danger' : 'text-white' ?>">
                                <i class="fa-solid fa-tags me-2"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('admin-carousel') ?>" class="nav-link <?= str_starts_with($currentPage, 'admin-carousel') || str_starts_with($currentPage, 'admin-add-carousel') || str_starts_with($currentPage, 'admin-edit-carousel') ? 'active bg-danger' : 'text-white' ?>">
                                <i class="fa-solid fa-images me-2"></i> Home Carousel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('admin-inquiries') ?>" class="nav-link <?= str_starts_with($currentPage, 'admin-inquiries') ? 'active bg-danger' : 'text-white' ?>">
                                <i class="fa-solid fa-envelope me-2"></i> Inquiries
                            </a>
                        </li>
                    </ul>
                </aside>
                <div class="col-md-9 col-lg-10 p-4 admin-content">
        <?php endif; ?>
