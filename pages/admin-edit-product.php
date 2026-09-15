<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$productId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();
if (!$product) redirect('admin-dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $categoryStmt = $pdo->prepare('SELECT discount_percent FROM categories WHERE id = :id');
    $categoryStmt->execute(['id' => $categoryId]);
    $category = $categoryStmt->fetch();
    $actualPrice = (float)($_POST['actual_price'] ?? 0);
    $discountPrice = round($actualPrice * (1 - (float)($category['discount_percent'] ?? 80) / 100), 2);
    $imagePath = $product['image_url'];
    if (!empty($_FILES['image']['name'])) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = 'Upload a valid image smaller than 5 MB.';
        }
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!$error && (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($_FILES['image']['tmp_name']) === false)) $error = 'Invalid image type.';
        else {
            $filename = bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadDir = __DIR__ . '/../templates/static/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) $error = 'The image could not be saved.';
            else $imagePath = 'uploads/' . $filename;
        }
    }
    if (!$error) {
        $stmt = $pdo->prepare('UPDATE products SET name = :name, category_id = :category_id, brand_id = :brand_id, actual_price = :actual_price, discount_price = :discount_price, image_url = :image_url, is_stock = :is_stock WHERE id = :id');
        $stmt->execute(['name' => trim($_POST['name'] ?? ''), 'category_id' => $categoryId, 'brand_id' => (int)($_POST['brand_id'] ?? 0) ?: null, 'actual_price' => $actualPrice, 'discount_price' => $discountPrice, 'image_url' => $imagePath, 'is_stock' => !empty($_POST['is_stock']) ? 1 : 0, 'id' => $productId]);
        redirect('admin-dashboard');
    }
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();
?>

<section>
    <h2 class="fw-bold mb-4">Edit Product</h2>
    <div class="card shadow-sm border-0 p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="mb-3">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select"><option value="">No brand</option><?php foreach ($brands as $brand): ?><option value="<?= (int)$brand['id'] ?>" <?= (int)$product['brand_id'] === (int)$brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="mb-3">
                <label class="form-label">Actual Price</label>
                <input type="number" name="actual_price" class="form-control" value="<?= htmlspecialchars($product['actual_price']) ?>" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Product Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="form-check mb-3"><input type="checkbox" name="is_stock" class="form-check-input" <?= !empty($product['is_stock']) ? 'checked' : '' ?>><label class="form-check-label">In stock</label></div>
            <button type="submit" class="btn btn-danger">Update Product</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
