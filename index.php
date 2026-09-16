<?php
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        include __DIR__ . '/pages/home.php';
        break;
    case 'products':
        include __DIR__ . '/pages/products.php';
        break;
    case 'product-detail':
        include __DIR__ . '/pages/product-detail.php';
        break;
    case 'cart':
        include __DIR__ . '/pages/cart.php';
        break;
    case 'login':
        include __DIR__ . '/pages/login.php';
        break;
    case 'signup':
        include __DIR__ . '/pages/signup.php';
        break;
    case 'account':
        include __DIR__ . '/pages/account.php';
        break;
    case 'forgot-password':
        include __DIR__ . '/pages/forgot-password.php';
        break;
    case 'reset-password':
        include __DIR__ . '/pages/reset-password.php';
        break;
    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=home');
            exit;
        }
        require_once __DIR__ . '/includes/header.php';
        verify_csrf();
        session_unset();
        session_destroy();
        header('Location: index.php?page=home');
        exit;
    case 'admin-login':
        include __DIR__ . '/pages/admin-login.php';
        break;
    case 'admin-dashboard':
        include __DIR__ . '/pages/admin-dashboard.php';
        break;
    case 'admin-add-product':
        include __DIR__ . '/pages/admin-add-product.php';
        break;
    case 'admin-category-products':
        include __DIR__ . '/pages/admin-category-products.php';
        break;
    case 'admin-edit-product':
        include __DIR__ . '/pages/admin-edit-product.php';
        break;
    case 'admin-inquiries':
        include __DIR__ . '/pages/admin-inquiries.php';
        break;
    case 'admin-download-inquiry':
        include __DIR__ . '/pages/admin-download-inquiry.php';
        break;
    case 'admin-categories':
        include __DIR__ . '/pages/admin-categories.php';
        break;
    case 'admin-add-category':
        include __DIR__ . '/pages/admin-add-category.php';
        break;
    case 'admin-edit-category':
        include __DIR__ . '/pages/admin-edit-category.php';
        break;
    case 'admin-carousel':
        include __DIR__ . '/pages/admin-carousel.php';
        break;
    case 'admin-add-carousel':
        include __DIR__ . '/pages/admin-add-carousel.php';
        break;
    case 'admin-edit-carousel':
        include __DIR__ . '/pages/admin-edit-carousel.php';
        break;
    case 'admin-logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-login');
            exit;
        }
        require_once __DIR__ . '/includes/header.php';
        verify_csrf();
        session_unset();
        session_destroy();
        header('Location: index.php?page=admin-login');
        exit;
    case 'about':
        include __DIR__ . '/pages/about.php';
        break;
    case 'contact':
        include __DIR__ . '/pages/contact.php';
        break;
    case 'terms':
        include __DIR__ . '/pages/terms.php';
        break;
    case 'privacy':
        include __DIR__ . '/pages/privacy.php';
        break;
    default:
        include __DIR__ . '/pages/home.php';
        break;
}
