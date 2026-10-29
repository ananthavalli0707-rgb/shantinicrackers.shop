<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$productId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT products.*, categories.name AS category_name, categories.discount_percent AS cat_discount 
                       FROM products 
                       LEFT JOIN categories ON categories.id = products.category_id 
                       WHERE products.id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_msg'] = 'Product not found.';
    $_SESSION['flash_type'] = 'danger';
    redirect('admin-product-details');
}

$error = null;
$categories = $pdo->query('SELECT id, name, discount_percent FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();

// Pre-create category discount mapping for JS live calculation
$categoryDiscounts = [];
foreach ($categories as $cat) {
    $categoryDiscounts[$cat['id']] = (float)($cat['discount_percent'] ?? 80);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Check if delete action from edit page
    if (($_POST['action'] ?? '') === 'delete') {
        $delStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $delStmt->execute(['id' => $productId]);
        if (!empty($product['image_url']) && str_starts_with($product['image_url'], 'uploads/') && file_exists(__DIR__ . '/../templates/static/' . $product['image_url'])) {
            @unlink(__DIR__ . '/../templates/static/' . $product['image_url']);
        }
        $_SESSION['flash_msg'] = 'Product "' . $product['name'] . '" deleted successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('admin-product-details');
    }

    $name = trim($_POST['name'] ?? '');
    $tamilName = trim($_POST['tamil_name'] ?? '') ?: null;
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brandId = (int)($_POST['brand_id'] ?? 0) ?: null;
    $actualPrice = (float)($_POST['actual_price'] ?? 0);
    $isStock = !empty($_POST['is_stock']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '') ?: null;

    if ($name === '') {
        $error = 'Product name is required.';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a valid category.';
    } elseif ($actualPrice <= 0) {
        $error = 'Actual price must be greater than zero.';
    } else {
        $discountPercent = $categoryDiscounts[$categoryId] ?? 80.0;
        $discountPrice = round($actualPrice * (1 - $discountPercent / 100), 2);
        $imagePath = $product['image_url'];

        // Handle image update
        if (!empty($_FILES['image']['name'])) {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
                $error = 'Upload a valid image smaller than 5 MB.';
            } else {
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($_FILES['image']['tmp_name']) === false) {
                    $error = 'Please upload a valid image file (JPG, PNG, WEBP, or GIF).';
                } else {
                    $filename = bin2hex(random_bytes(8)) . '.' . $extension;
                    $uploadDir = __DIR__ . '/../templates/static/uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
                        $error = 'The image could not be saved to disk.';
                    } else {
                        // Unlink previous image if it was an upload
                        if (!empty($product['image_url']) && str_starts_with($product['image_url'], 'uploads/') && file_exists($uploadDir . '/' . basename($product['image_url']))) {
                            @unlink($uploadDir . '/' . basename($product['image_url']));
                        }
                        $imagePath = 'uploads/' . $filename;
                    }
                }
            }
        }

        if (!$error) {
            $updateStmt = $pdo->prepare('UPDATE products SET 
                name = :name, 
                tamil_name = :tamil_name, 
                category_id = :category_id, 
                brand_id = :brand_id, 
                actual_price = :actual_price, 
                discount_price = :discount_price, 
                image_url = :image_url, 
                is_stock = :is_stock, 
                description = :description 
                WHERE id = :id');
            
            $updateStmt->execute([
                'name' => $name,
                'tamil_name' => $tamilName,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'actual_price' => $actualPrice,
                'discount_price' => $discountPrice,
                'image_url' => $imagePath,
                'is_stock' => $isStock,
                'description' => $description,
                'id' => $productId
            ]);

            $_SESSION['flash_msg'] = 'Product details for "' . $name . '" updated successfully!';
            $_SESSION['flash_type'] = 'success';
            redirect('admin-product-details');
        }
    }
}
?>

<section class="admin-edit-product-detail-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= url('admin-product-details') ?>" class="text-decoration-none text-muted small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Product Details
                </a>
                <span class="text-muted">•</span>
                <span class="badge bg-secondary-subtle text-secondary">Product #<?= (int)$product['id'] ?></span>
            </div>
            <h2 class="fw-bold mb-0 text-dark">Edit Product Details</h2>
            <p class="text-muted small mb-0">Editing details for: <strong><?= htmlspecialchars($product['name']) ?></strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('product-detail', ['id' => $product['id']]) ?>" target="_blank" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-external-link"></i> View on Storefront
            </a>
            <a href="<?= url('admin-product-details') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editProductForm">
        <?= csrf_field() ?>
        
        <div class="row g-4">
            <!-- Left Column: Core Product Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                        <i class="fa-solid fa-circle-info me-2 text-primary"></i>Basic Product Information
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">English Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="productName" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Tamil Name <span class="badge bg-primary-subtle text-primary small ms-1">தமிழ் பெயர்</span>
                            </label>
                            <input type="text" name="tamil_name" id="tamilName" class="form-control" placeholder="எ.கா. 5&#34; மெகா டீலக்ஸ்" value="<?= htmlspecialchars($_POST['tamil_name'] ?? ($product['tamil_name'] ?? '')) ?>">
                            <small class="text-muted">Displays next to the English name in brackets on the storefront.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="categorySelect" class="form-select" required>
                                <option value="">-- Choose Category --</option>
                                <?php 
                                $selectedCat = (int)($_POST['category_id'] ?? $product['category_id']);
                                foreach ($categories as $cat): 
                                ?>
                                    <option value="<?= (int)$cat['id'] ?>" data-discount="<?= htmlspecialchars($cat['discount_percent']) ?>" <?= $selectedCat === (int)$cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['discount_percent']) ?>% Discount)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand (Optional)</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- No Brand / Generic --</option>
                                <?php 
                                $selectedBrand = (int)($_POST['brand_id'] ?? $product['brand_id']);
                                foreach ($brands as $brand): 
                                ?>
                                    <option value="<?= (int)$brand['id'] ?>" <?= $selectedBrand === (int)$brand['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Product Description Card -->
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                        <i class="fa-solid fa-align-left me-2 text-info"></i>Detailed Product Description
                    </h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Description & Specifications</label>
                        <textarea name="description" id="productDescription" class="form-control" rows="6" placeholder="Enter specifications, pieces per box, sound/light effect description, safety precautions, etc..."><?= htmlspecialchars($_POST['description'] ?? ($product['description'] ?? '')) ?></textarea>
                        <small class="text-muted">This detailed description appears on the customer's <strong>Product Detail</strong> page.</small>
                    </div>
                </div>
            </div>

            <!-- Right Column: Pricing, Stock, Image & Delete -->
            <div class="col-lg-4">
                <!-- Pricing Card -->
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                        <i class="fa-solid fa-tags me-2 text-success"></i>Pricing & Discount
                    </h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Actual Price (MRP ₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" name="actual_price" id="actualPriceInput" class="form-control" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['actual_price'] ?? $product['actual_price']) ?>" required>
                        </div>
                    </div>

                    <!-- Live Calculation Preview Box -->
                    <div class="p-3 rounded-3 bg-light border mb-3" id="pricePreviewBox">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Category Discount:</span>
                            <span class="fw-bold text-danger" id="discountPercentBadge">0%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Customer Saves:</span>
                            <span class="fw-bold text-success" id="savingsAmountPreview">₹0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-dark">Estimate Price:</span>
                            <span class="fs-5 fw-bold text-primary" id="discountPricePreview">₹0.00</span>
                        </div>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <?php $isStockChecked = isset($_POST['name']) ? !empty($_POST['is_stock']) : !empty($product['is_stock']); ?>
                        <input class="form-check-input" type="checkbox" name="is_stock" id="isStockSwitch" value="1" <?= $isStockChecked ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isStockSwitch">Available In Stock</label>
                    </div>
                </div>

                <!-- Product Image Card -->
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                        <i class="fa-solid fa-image me-2 text-warning"></i>Product Image
                    </h5>

                    <div class="text-center mb-3">
                        <div id="imagePreviewContainer" class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-2 overflow-hidden position-relative" style="width: 140px; height: 140px; border: 2px dashed #ced4da;">
                            <?php if (!empty($product['image_url'])): ?>
                                <img id="imagePreviewElement" src="<?= asset($product['image_url']) ?>" alt="Current Image" class="w-100 h-100 object-fit-cover">
                                <span class="text-muted small d-none" id="noImageText"><i class="fa-solid fa-cloud-arrow-up fs-2 d-block mb-1"></i>Select Image</span>
                            <?php else: ?>
                                <span class="text-muted small" id="noImageText"><i class="fa-solid fa-cloud-arrow-up fs-2 d-block mb-1"></i>No Image</span>
                                <img id="imagePreviewElement" src="#" alt="Preview" class="w-100 h-100 object-fit-cover d-none">
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($product['image_url'])): ?>
                            <small class="text-muted d-block">Current: <code><?= htmlspecialchars(basename($product['image_url'])) ?></code></small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Upload New Image (Optional)</label>
                        <input type="file" name="image" id="imageInput" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small class="text-muted">Leave blank to keep existing image.</small>
                    </div>
                </div>

                <!-- Save & Delete Actions -->
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mb-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Update Product Details
                    </button>
                    <a href="<?= url('admin-product-details') ?>" class="btn btn-light w-100 text-muted">Cancel</a>
                </div>

                <!-- Delete Action Form -->
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-top border-danger border-2">
                    <span class="text-danger small fw-bold text-uppercase d-block mb-1">Danger Zone</span>
                    <p class="small text-muted mb-2">Permanently remove this product and its details from the catalog.</p>
                    <button type="submit" form="deleteForm" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Are you completely sure you want to delete this product? This action cannot be undone.');">
                        <i class="fa-solid fa-trash me-1"></i> Delete This Product
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden form for deletion -->
    <form id="deleteForm" method="POST" action="<?= url('admin-edit-product-detail', ['id' => $product['id']]) ?>" class="d-none">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('categorySelect');
    const actualPriceInput = document.getElementById('actualPriceInput');
    const discountPercentBadge = document.getElementById('discountPercentBadge');
    const savingsAmountPreview = document.getElementById('savingsAmountPreview');
    const discountPricePreview = document.getElementById('discountPricePreview');
    const imageInput = document.getElementById('imageInput');
    const imagePreviewElement = document.getElementById('imagePreviewElement');
    const noImageText = document.getElementById('noImageText');

    const categoryDiscounts = <?= json_encode($categoryDiscounts) ?>;

    function recalculatePrice() {
        const catId = categorySelect.value;
        const discountPercent = categoryDiscounts[catId] !== undefined ? categoryDiscounts[catId] : 80;
        const actual = parseFloat(actualPriceInput.value) || 0;
        const estimate = Math.round(actual * (1 - (discountPercent / 100)) * 100) / 100;
        const savings = Math.max(0, actual - estimate);

        discountPercentBadge.textContent = discountPercent + '%';
        savingsAmountPreview.textContent = '₹' + savings.toFixed(2);
        discountPricePreview.textContent = '₹' + estimate.toFixed(2);
    }

    if (categorySelect && actualPriceInput) {
        categorySelect.addEventListener('change', recalculatePrice);
        actualPriceInput.addEventListener('input', recalculatePrice);
        recalculatePrice();
    }

    // Image file preview
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreviewElement.src = e.target.result;
                    imagePreviewElement.classList.remove('d-none');
                    if (noImageText) noImageText.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
