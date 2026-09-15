<?php require __DIR__ . '/../includes/header.php'; ?>

<?php
$products = $pdo->query('SELECT products.*, products.image_url AS image, categories.id AS category_id, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id ORDER BY categories.name, products.name')->fetchAll();
$productGroups = [];
$totalProducts = count($products);

foreach ($products as $product) {
    $groupId = !empty($product['category_id']) ? 'category-' . (int)$product['category_id'] : 'uncategorized';
    if (!isset($productGroups[$groupId])) {
        $productGroups[$groupId] = [
            'name' => $product['category_name'] ?: 'Uncategorized',
            'products' => [],
        ];
    }
    $productGroups[$groupId]['products'][] = $product;
}
$totalCategories = count($productGroups);
?>

<div class="section-padding bg-surface">
    <div class="container">
        <!-- Header Title Section -->
        <div class="row align-items-end mb-5">
            <div class="col-12 col-md-7 mb-3 mb-md-0">
                <span class="section-kicker">Browse our collection</span>
                <h1 class="h1-display mb-0">Our Catalog</h1>
            </div>
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="categorySearchInput" class="form-control border-start-0 ps-0" placeholder="Search products..." aria-label="Search products">
                </div>
            </div>
        </div>

        <div class="row g-5">
            <!-- LEFT SIDEBAR CATEGORY NAVIGATION -->
            <aside class="col-12 col-lg-3">
                <button class="btn-primary w-100 d-lg-none mb-3 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#categorySidebarCollapse">
                    <span><i class="fa-solid fa-layer-group me-2"></i>Filter Categories</span>
                    <span class="badge bg-white text-primary"><?= $totalCategories ?></span>
                </button>

                <div class="collapse d-lg-block sticky-top" id="categorySidebarCollapse" style="top: 100px;">
                    <div class="category-sidebar">
                        <h4 class="h3-card mb-3 d-none d-lg-block">Categories</h4>
                        <div class="category-list" id="sidebarCategoryList">
                            <a href="#" class="category-link active" data-category="all">
                                <span>All Products</span>
                                <span class="badge"><?= $totalProducts ?></span>
                            </a>
                            <?php foreach ($productGroups as $groupId => $group): ?>
                                <a href="#<?= htmlspecialchars($groupId) ?>" class="category-link sidebar-category-btn" data-category="<?= htmlspecialchars($groupId) ?>">
                                    <span><?= htmlspecialchars($group['name']) ?></span>
                                    <span class="badge"><?= count($group['products']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- MAIN PRODUCTS CONTENT AREA -->
            <main class="col-12 col-lg-9">
                <?php if (!$productGroups): ?>
                    <div class="alert alert-info shadow-sm p-4 text-center">No products are available at the moment.</div>
                <?php endif; ?>

                <div id="productCatalogContainer">
                    <?php foreach ($productGroups as $groupId => $group): ?>
                        <section id="<?= htmlspecialchars($groupId) ?>" class="product-category-section mb-5" data-category-group="<?= htmlspecialchars($groupId) ?>">
                            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                                <h2 class="h2-section mb-0"><?= htmlspecialchars($group['name']) ?></h2>
                                <span class="badge bg-primary rounded-pill px-3 py-2 text-white"><?= count($group['products']) ?> items</span>
                            </div>

                            <div class="row g-4">
                                <?php foreach ($group['products'] as $product): ?>
                                    <div class="col-12 col-sm-6 col-md-4 product-item-col" id="product-<?= (int)$product['id'] ?>" data-product-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="catalog-card">
                                            <a href="<?= url('product-detail', ['id' => $product['id']]) ?>">
                                                <div class="catalog-image-wrap">
                                                    <?php if (strtolower($product['category_name'] ?? '') !== 'gift box'): ?>
                                                        <span class="offer-badge">Offer</span>
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
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                    <div id="noSearchResults" class="alert alert-warning text-center p-4 rounded-3 shadow-sm d-none">
                        <h3 class="h3-card mb-2">No products match your search</h3>
                        <p class="mb-0 text-muted">Try searching with another term.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('categorySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            let foundAny = false;
            
            document.querySelectorAll('.product-item-col').forEach(function(item) {
                if (item.getAttribute('data-product-name').indexOf(term) > -1) {
                    item.style.display = '';
                    foundAny = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Hide empty sections
            document.querySelectorAll('.product-category-section').forEach(function(section) {
                const visibleItems = section.querySelectorAll('.product-item-col[style=""]').length + 
                                     section.querySelectorAll('.product-item-col:not([style*="display: none"])').length;
                if (visibleItems === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });
            
            const noRes = document.getElementById('noSearchResults');
            if(noRes) {
                if (!foundAny && term.length > 0) noRes.classList.remove('d-none');
                else noRes.classList.add('d-none');
            }
        });
    }
    
    // Category filter logic
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            document.querySelectorAll('.category-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.getAttribute('data-category');
            
            document.querySelectorAll('.product-category-section').forEach(section => {
                if (category === 'all' || section.getAttribute('data-category-group') === category) {
                    section.style.display = '';
                } else {
                    section.style.display = 'none';
                }
            });
            
            if(window.innerWidth < 992) {
                const sidebarEl = document.getElementById('categorySidebarCollapse');
                if (sidebarEl.classList.contains('show')) {
                    sidebarEl.classList.remove('show');
                }
            }
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
