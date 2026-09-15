<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    redirect('admin-categories');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $discountPercent = (float)($_POST['discount_percent'] ?? 0);
    
    $imagePath = $category['image_url'];
    if (!empty($_FILES['image']['name'])) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = 'Upload a valid image smaller than 5 MB.';
        }
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!$error && (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($_FILES['image']['tmp_name']) === false)) {
            $error = 'Please upload a JPG, PNG, WEBP, or GIF image.';
        } else {
            $filename = 'cat_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadDir = __DIR__ . '/../templates/static/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
                $error = 'The image could not be saved.';
            } else {
                if ($imagePath && file_exists(__DIR__ . '/../templates/static/' . $imagePath)) {
                    @unlink(__DIR__ . '/../templates/static/' . $imagePath);
                }
                $imagePath = 'uploads/' . $filename;
            }
        }
    }

    if (!$error && $name !== '') {
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, image_url = :image_url, discount_percent = :discount_percent WHERE id = :id');
        $stmt->execute(['name' => $name, 'image_url' => $imagePath, 'discount_percent' => $discountPercent, 'id' => $id]);
        
        // Update all products in this category with the new discount percentage
        $updateProducts = $pdo->prepare('UPDATE products SET discount_price = ROUND(actual_price * (1 - :discount_percent / 100), 2) WHERE category_id = :category_id');
        $updateProducts->execute(['discount_percent' => $discountPercent, 'category_id' => $id]);
        
        redirect('admin-categories');
    }
    if (!$error && $name === '') $error = 'Category name is required.';
}
?>

<section>
    <h2 class="fw-bold mb-4">Edit Category</h2>
    <div class="card shadow-sm border-0 p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category Image (Optional)</label>
                <?php if ($category['image_url']): ?>
                    <div class="mb-2">
                        <img src="<?= asset($category['image_url']) ?>" alt="Current Image" class="img-thumbnail" style="max-height: 100px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="mb-3">
                <label class="form-label">Discount Percentage</label>
                <input type="number" name="discount_percent" class="form-control" min="0" max="100" step="0.01" value="<?= htmlspecialchars($category['discount_percent'] ?? '0') ?>">
                <div class="form-text">Percentage discount to apply to products in this category by default.</div>
            </div>
            <button type="submit" class="btn btn-danger">Update Category</button>
            <a href="<?= url('admin-categories') ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
