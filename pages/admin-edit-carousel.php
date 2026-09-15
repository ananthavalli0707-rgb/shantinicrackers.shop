<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM home_carousel WHERE id = :id');
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    redirect('admin-carousel');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    
    $imagePath = $item['image_url'];
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
                // Delete old image if new one is uploaded successfully
                if ($imagePath && file_exists(__DIR__ . '/../templates/static/' . $imagePath)) {
                    @unlink(__DIR__ . '/../templates/static/' . $imagePath);
                }
                $imagePath = 'uploads/' . $filename;
            }
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare('UPDATE home_carousel SET image_url = :image_url, title = :title, description = :description, link = :link, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $stmt->execute([
            'image_url' => $imagePath,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
            'id' => $id
        ]);
        redirect('admin-carousel');
    }
}
?>

<section>
    <h2 class="fw-bold mb-4">Edit Carousel Slide</h2>
    <div class="card shadow-sm border-0 p-4">
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Current Image</label>
                <?php if ($item['image_url']): ?>
                    <div class="mb-2">
                        <img src="<?= asset($item['image_url']) ?>" alt="Current Image" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="form-text">Leave empty to keep the current image. Recommended size: 1920x600 pixels.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Title (Optional)</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($item['title'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Link URL (Optional)</label>
                <input type="text" name="link" class="form-control" value="<?= htmlspecialchars($item['link'] ?? '') ?>" placeholder="https://... or index.php?page=products">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $item['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">
                            Active (Show on homepage)
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-danger">Update Slide</button>
            <a href="<?= url('admin-carousel') ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
