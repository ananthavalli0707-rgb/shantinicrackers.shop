<?php require __DIR__ . '/../includes/header.php'; ?>

<?php
$id = (int)($_GET['id'] ?? 0);
$product = $pdo->prepare("SELECT products.*, products.image_url AS image, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id WHERE products.id = :id");
$product->execute(['id' => $id]);
$product = $product->fetch();

if (!$product):
    echo '<div class="section-padding bg-surface text-center"><div class="container"><div class="alert alert-warning shadow-sm">Product not found.</div><a href="'.url('products').'" class="btn-primary mt-3">Back to Products</a></div></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
endif;
?>

<div class="section-padding bg-surface">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('home') ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('products') ?>">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row g-5 align-items-center bg-white p-4 p-md-5 rounded shadow-sm" style="border: 1px solid var(--border-color);">
            <div class="col-md-5 text-center">
                <div class="position-relative d-inline-block">
                    <?php if (strtolower($product['category_name'] ?? '') !== 'gift box'): ?>
                        <span class="offer-badge" style="top: 1rem; right: 1rem;">Offer</span>
                    <?php endif; ?>
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= asset('uploads/' . $product['image']) ?>" class="img-fluid rounded" style="max-height: 400px; object-fit: contain;" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="text-secondary text-center bg-light rounded d-flex align-items-center justify-content-center" style="font-size: 6rem; width: 100%; aspect-ratio: 1;">🧨</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-7">
                <span class="card-category text-accent mb-2 d-block fw-bold"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                <h1 class="h1-display mb-3" style="font-size: clamp(2rem, 3vw, 2.5rem);"><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="mb-4 pb-4 border-bottom">
                    <p class="text-muted fs-5 mb-0"><?= htmlspecialchars($product['description'] ?? 'Premium quality product for your celebrations.') ?></p>
                </div>
                
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="price-current fs-2" style="color: var(--primary);">₹<?= htmlspecialchars($product['discount_price']) ?></span>
                    <?php if (!empty($product['actual_price']) && $product['actual_price'] != $product['discount_price']): ?>
                        <span class="price-old fs-5 text-muted text-decoration-line-through">₹<?= htmlspecialchars($product['actual_price']) ?></span>
                        <span class="badge py-2 px-3 rounded-pill fw-bold" style="background-color: var(--accent); color: #fff;">
                            Save ₹<?= (int)$product['actual_price'] - (int)$product['discount_price'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <form action="index.php?page=cart" method="POST" class="mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="form-label text-muted fw-bold mb-0">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <div class="quantity-control mb-0" style="width: 140px;">
                                <button type="button" class="quantity-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                                <input type="number" name="quantity" class="quantity-input" value="1" min="1">
                                <button type="button" class="quantity-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                            </div>
                        </div>
                        <div class="col-12 col-sm-auto mt-4 mt-sm-0 flex-grow-1">
                            <button type="submit" class="btn-primary w-100 py-3 text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="fa-solid fa-cart-plus me-2"></i> Add to Estimate
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-5 p-4 bg-light rounded text-center">
                    <p class="mb-0 text-muted small"><i class="fa-solid fa-truck-fast me-2 text-primary"></i> Easy enquiry process. Add products to estimate and submit via WhatsApp.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
