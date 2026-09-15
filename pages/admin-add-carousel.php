<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
            $error = 'Upload a valid image smaller than 5 MB.';
        }
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!$error && (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || @getimagesize($_FILES['image']['tmp_name']) === false)) {
            $error = 'Please upload a JPG, PNG, WEBP, or GIF image.';
        } else {
            $filename = 'carousel_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadDir = __DIR__ . '/../templates/static/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $filename)) {
                $error = 'The image could not be saved.';
            } else {
                $imagePath = 'uploads/' . $filename;
            }
        }
    } else {
        $error = 'An image is required for the carousel.';
    }

    if (!$error) {
        $stmt = $pdo->prepare('INSERT INTO home_carousel (image_url, title, description, link, is_active, sort_order) VALUES (:image_url, :title, :description, :link, :is_active, :sort_order)');
        $stmt->execute([
            'image_url' => $imagePath,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'is_active' => $isActive,
            'sort_order' => $sortOrder
        ]);
        redirect('admin-carousel');
    }
}
?>

<section>
    <h2 class="fw-bold mb-4">Add Carousel Slide</h2>
    <div class="card shadow-sm border-0 p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Image (Required)</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
                <div class="form-text">Recommended size: 1920x600 pixels.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Title (Optional)</label>
                <input type="text" name="title" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Link URL (Optional)</label>
                <input type="text" name="link" class="form-control" placeholder="https://... or index.php?page=products">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                        <label class="form-check-label" for="isActive">
                            Active (Show on homepage)
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-danger">Save Slide</button>
            <a href="<?= url('admin-carousel') ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
