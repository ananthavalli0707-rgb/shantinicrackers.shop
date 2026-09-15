<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $actualPrice = (float)($_POST['actual_price'] ?? 0);
    $categoryStmt = $pdo->prepare('SELECT discount_percent FROM categories WHERE id = :id');
    $categoryStmt->execute(['id' => $categoryId]);
    $category = $categoryStmt->fetch();
    $discountPercent = (float)($category['discount_percent'] ?? 80);
    $discountPrice = round($actualPrice * (1 - $discountPercent / 100), 2);
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = 'Upload a valid image smaller than 5 MB.';
        }
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!$error && (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($_FILES['image']['tmp_name']) === false)) {
            $error = 'Please upload a JPG, PNG, WEBP, or GIF image.';
        } else {
            $filename = bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadDir = __DIR__ . '/../templates/static/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
                $error = 'The image could not be saved.';
            } else {
                $imagePath = 'uploads/' . $filename;
            }
        }
    }
    if (!$error && $name !== '' && $categoryId > 0 && $actualPrice > 0) {
        $stmt = $pdo->prepare('INSERT INTO products (name, category_id, brand_id, actual_price, discount_price, image_url, is_stock) VALUES (:name, :category_id, :brand_id, :actual_price, :discount_price, :image_url, 1)');
        $stmt->execute(['name' => $name, 'category_id' => $categoryId, 'brand_id' => $brandId ?: null, 'actual_price' => $actualPrice, 'discount_price' => $discountPrice, 'image_url' => $imagePath]);
        redirect('admin-dashboard');
    }
    if (!$error) $error = 'Product name, category, and a valid price are required.';
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();
?>

<section>
    <h2 class="fw-bold mb-4">Add New Product</h2>
    <div class="card shadow-sm border-0 p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="mb-3">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select"><option value="">Select brand</option><?php foreach ($brands as $brand): ?><option value="<?= (int)$brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="mb-3">
                <label class="form-label">Actual Price</label>
                <input type="number" name="actual_price" class="form-control" min="0" step="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Product Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-danger">Save Product</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
