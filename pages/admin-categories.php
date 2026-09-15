<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $stmt->execute(['id' => (int)($_POST['category_id'] ?? 0)]);
    redirect('admin-categories');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll();
?>

<section>
    <h2 class="fw-bold mb-4">Manage Categories</h2>
    <div class="d-flex gap-2 mb-4">
        <a href="<?= url('admin-add-category') ?>" class="btn btn-danger">Add Category</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white shadow-sm admin-table">
            <thead class="table-light"><tr><th>ID</th><th>Image</th><th>Name</th><th>Discount %</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= (int)$category['id'] ?></td>
                    <td>
                        <?php if ($category['image_url']): ?>
                            <img src="<?= asset($category['image_url']) ?>" alt="" style="height: 40px; width: auto; border-radius: 4px;">
                        <?php else: ?>
                            <span class="text-muted small">No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($category['name']) ?></td>
                    <td><?= htmlspecialchars($category['discount_percent'] ?? '0') ?>%</td>
                    <td class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin-edit-category', ['id' => $category['id']]) ?>">Edit</a>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$categories): ?><div class="alert alert-info">No categories found.</div><?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
