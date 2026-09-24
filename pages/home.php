<?php require __DIR__ . '/../includes/header.php'; ?>

<?php
$carouselItems = [];
try {
    $carouselItems = $pdo->query("SELECT * FROM home_carousel WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();
} catch (Exception $e) {
    // Suppress error if table doesn't exist yet
}
?>

<?php if ($carouselItems): ?>
<!-- Dynamic Carousel Section -->
<section class="hero-carousel-section">
    <div id="homeCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($carouselItems as $index => $item): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= asset($item['image_url']) ?>" class="d-block w-100" alt="<?= htmlspecialchars($item['title'] ?? 'Banner') ?>">
                    <?php if (!empty($item['title']) || !empty($item['description']) || !empty($item['link'])): ?>
                    <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100 top-0 text-center" style="background: rgba(0,0,0,0.4);">
                        <div class="p-4" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); border-radius: var(--radius-md); max-width: 800px;">
                            <?php if (!empty($item['title'])): ?>
                                <h1 class="h1-display mb-3 text-white"><?= htmlspecialchars($item['title']) ?></h1>
                            <?php endif; ?>
                            <?php if (!empty($item['description'])): ?>
                                <p class="hero-subtitle text-light mb-4"><?= htmlspecialchars($item['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['link'])): ?>
                                <a href="<?= htmlspecialchars($item['link']) ?>" class="btn-primary">Explore Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($carouselItems) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<!-- Static Hero Section Fallback -->
<section class="hero-section">
    <div class="container hero-content text-center">
        <h1 class="h1-display mb-3">Celebrate with Shantini</h1>
        <p class="hero-subtitle mb-4">Premium fireworks for unforgettable moments and festive celebrations.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= url('products') ?>" class="btn-accent">Explore Products</a>
            <a href="<?= url('cart') ?>" class="btn-primary">Build Your Estimate</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- New Arrivals Section -->
<?php
$newArrivals = $pdo->query("SELECT products.*, products.image_url AS image, categories.name AS category_name FROM products JOIN categories ON categories.id = products.category_id WHERE categories.name = 'New Arrivals' ORDER BY products.id DESC")->fetchAll();
if ($newArrivals):
?>
<section class="section-padding bg-surface border-bottom">
    <div class="container">
        <div class="section-header">
            <span class="section-kicker">Latest Collection</span>
            <h2 class="h2-section">New Arrivals <span class="badge bg-danger ms-2" style="font-size: 0.9rem; vertical-align: middle;">HOT</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($newArrivals as $product): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="catalog-card">
                        <a href="<?= url('product-detail', ['id' => $product['id']]) ?>">
                            <div class="catalog-image-wrap">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= asset('uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div class="text-secondary font-monospace" style="font-size: 3rem;">🧨</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body-content">
                            <span class="card-category"><?= htmlspecialchars($product['category_name']) ?></span>
                            <a href="<?= url('product-detail', ['id' => $product['id']]) ?>" class="text-decoration-none">
                                <h3 class="card-product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            </a>
                            <div class="card-price-wrap mt-auto">
                                <span class="price-current">₹<?= htmlspecialchars($product['discount_price']) ?></span>
                                <?php if (!empty($product['actual_price']) && $product['actual_price'] != $product['discount_price']): ?>
                                    <span class="price-old">₹<?= htmlspecialchars($product['actual_price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <form action="index.php?page=cart" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1">
                                    <button type="button" class="quantity-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                                </div>
                                <button type="submit" class="btn-primary w-100">Add to Estimate</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Child Crackers Section -->
<?php
$childCrackers = $pdo->query("SELECT products.*, products.image_url AS image, categories.name AS category_name FROM products JOIN categories ON categories.id = products.category_id WHERE categories.name = 'Child Crackers' ORDER BY products.id DESC")->fetchAll();
if ($childCrackers):
?>
<section class="section-padding bg-surface border-bottom" style="background-color: var(--bs-light) !important;">
    <div class="container">
        <div class="section-header">
            <span class="section-kicker">For The Little Ones</span>
            <h2 class="h2-section">Child Crackers <span class="badge bg-warning ms-2 text-dark" style="font-size: 0.9rem; vertical-align: middle;">KIDS</span></h2>
        </div>
        <div class="row g-4">
            <?php foreach ($childCrackers as $product): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="catalog-card">
                        <a href="<?= url('product-detail', ['id' => $product['id']]) ?>">
                            <div class="catalog-image-wrap">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= asset('uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div class="text-secondary font-monospace" style="font-size: 3rem;">🧨</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body-content">
                            <span class="card-category"><?= htmlspecialchars($product['category_name']) ?></span>
                            <a href="<?= url('product-detail', ['id' => $product['id']]) ?>" class="text-decoration-none">
                                <h3 class="card-product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            </a>
                            <div class="card-price-wrap mt-auto">
                                <span class="price-current">₹<?= htmlspecialchars($product['discount_price']) ?></span>
                                <?php if (!empty($product['actual_price']) && $product['actual_price'] != $product['discount_price']): ?>
                                    <span class="price-old">₹<?= htmlspecialchars($product['actual_price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <form action="index.php?page=cart" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1">
                                    <button type="button" class="quantity-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                                </div>
                                <button type="submit" class="btn-primary w-100">Add to Estimate</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Shop By Category -->
<style>
.category-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    background: var(--bs-white);
    border-radius: 50px;
    color: var(--bs-heading-color);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}
.category-pill:hover {
    background: var(--bs-primary);
    color: white;
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 20px rgba(var(--bs-primary-rgb), 0.25);
    border-color: var(--bs-primary);
}
.category-pill:hover .category-pill-icon {
    transform: rotate(15deg) scale(1.2);
}
.category-pill-icon {
    font-size: 1.25rem;
    transition: transform 0.3s ease;
}
</style>
<section class="section-padding">
    <div class="container">
        <div class="section-header text-center">
            <span class="section-kicker">Explore The Magic</span>
            <h2 class="h2-section">Shop By Category</h2>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
            <?php 
            foreach ($menuCategories as $category): 
                $catName = strtolower($category['name']);
                $icon = '🎇'; // default
                
                if (strpos($catName, 'new arrival') !== false) $icon = '🆕';
                elseif (strpos($catName, 'child') !== false || strpos($catName, 'kid') !== false) $icon = '🧸';
                elseif (strpos($catName, 'sparkler') !== false) $icon = '✨';
                elseif (strpos($catName, 'flower pot') !== false || strpos($catName, 'chakar') !== false || strpos($catName, 'chakkar') !== false) $icon = '💮';
                elseif (strpos($catName, 'bomb') !== false || strpos($catName, 'atom') !== false) $icon = '💣';
                elseif (strpos($catName, 'rocket') !== false) $icon = '🚀';
                elseif (strpos($catName, 'sky') !== false || strpos($catName, 'shot') !== false) $icon = '🌌';
                elseif (strpos($catName, 'gift') !== false || strpos($catName, 'box') !== false) $icon = '🎁';
                elseif (strpos($catName, 'fancy') !== false || strpos($catName, 'novel') !== false) $icon = '🎊';
                elseif (strpos($catName, 'garland') !== false || strpos($catName, 'wala') !== false || strpos($catName, 'lar') !== false) $icon = '🏮';
                elseif (strpos($catName, 'match') !== false) $icon = '🔥';
            ?>
            <a href="<?= url('products') ?>#category-<?= (int)$category['id'] ?>" class="category-pill">
                <span class="category-pill-icon"><?= $icon ?></span>
                <span class="category-pill-text"><?= htmlspecialchars($category['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Popular Picks -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-kicker">Customer Favourites</span>
            <h2 class="h2-section">Popular Picks</h2>
        </div>
        <div class="row g-4">
            <?php
            $products = $pdo->query("SELECT products.*, products.image_url AS image, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id LIMIT 8")->fetchAll();
            if ($products):
                foreach ($products as $product):
            ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="catalog-card">
                        <a href="<?= url('product-detail', ['id' => $product['id']]) ?>">
                            <div class="catalog-image-wrap">
                                <?php if (strtolower($product['category_name'] ?? '') !== 'gift box'): ?>
                                    <img src="<?= asset('uploads/offer%20logo.png') ?>" alt="Offer" class="offer-logo-img">
                                <?php endif; ?>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= asset('uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div class="text-secondary font-monospace" style="font-size: 3rem;">🧨</div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body-content">
                            <span class="card-category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                            <a href="<?= url('product-detail', ['id' => $product['id']]) ?>" class="text-decoration-none">
                                <h3 class="card-product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            </a>
                            <div class="card-price-wrap mt-auto">
                                <span class="price-current">₹<?= htmlspecialchars($product['discount_price']) ?></span>
                                <?php if (!empty($product['actual_price']) && $product['actual_price'] != $product['discount_price']): ?>
                                    <span class="price-old">₹<?= htmlspecialchars($product['actual_price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <form action="index.php?page=cart" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" onclick="this.nextElementSibling.stepDown()">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1">
                                    <button type="button" class="quantity-btn" onclick="this.previousElementSibling.stepUp()">+</button>
                                </div>
                                <button type="submit" class="btn-primary w-100">Add to Estimate</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="col-12 text-center text-muted"><p>No popular picks available.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Why Shantini Section -->
<section class="section-padding bg-surface">
    <div class="container">
        <div class="section-header">
            <span class="section-kicker">Why Choose Us</span>
            <h2 class="h2-section">Why Shantini?</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-12 col-md-4">
                <div class="p-4">
                    <i class="fa-solid fa-medal fs-1 text-accent mb-3"></i>
                    <h3 class="h3-card">Premium Quality</h3>
                    <p class="text-muted">Carefully selected fireworks for the best and safest celebration experience.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4">
                    <i class="fa-solid fa-tags fs-1 text-accent mb-3"></i>
                    <h3 class="h3-card">Competitive Pricing</h3>
                    <p class="text-muted">Best value for your money with regular offers on all product categories.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4">
                    <i class="fa-solid fa-clipboard-list fs-1 text-accent mb-3"></i>
                    <h3 class="h3-card">Easy Enquiry</h3>
                    <p class="text-muted">Simple estimate-based ordering system. Build your list and get a quick quote.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How To Order -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-kicker">Simple Process</span>
            <h2 class="h2-section">How To Order</h2>
        </div>
        <div class="row g-4 text-center position-relative">
            <!-- Add a subtle connecting line on desktop -->
            <div class="d-none d-md-block position-absolute top-50 start-50 translate-middle w-75 border-top border-2" style="z-index: 1;"></div>
            
            <div class="col-12 col-md-3 position-relative" style="z-index: 2;">
                <div class="bg-surface rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                    <span class="fs-4 fw-bold text-primary">01</span>
                </div>
                <h3 class="h3-card">Browse Products</h3>
                <p class="text-muted small">Explore our wide variety.</p>
            </div>
            <div class="col-12 col-md-3 position-relative" style="z-index: 2;">
                <div class="bg-surface rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                    <span class="fs-4 fw-bold text-primary">02</span>
                </div>
                <h3 class="h3-card">Add to Estimate</h3>
                <p class="text-muted small">Select items and quantities.</p>
            </div>
            <div class="col-12 col-md-3 position-relative" style="z-index: 2;">
                <div class="bg-surface rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                    <span class="fs-4 fw-bold text-primary">03</span>
                </div>
                <h3 class="h3-card">Review Estimate</h3>
                <p class="text-muted small">Check your estimated total.</p>
            </div>
            <div class="col-12 col-md-3 position-relative" style="z-index: 2;">
                <div class="bg-surface rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                    <span class="fs-4 fw-bold text-primary">04</span>
                </div>
                <h3 class="h3-card">Send Enquiry</h3>
                <p class="text-muted small">Submit to confirm order.</p>
            </div>
        </div>
    </div>
</section>

<!-- Strong Estimate CTA -->
<section class="section-padding bg-surface">
    <div class="container text-center">
        <h2 class="h2-section mb-4">Ready to celebrate?</h2>
        <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">Browse our full catalog and build your estimate today. We offer the best quality crackers for your special occasions.</p>
        <a href="<?= url('products') ?>" class="btn-primary btn-lg">Start Shopping</a>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
