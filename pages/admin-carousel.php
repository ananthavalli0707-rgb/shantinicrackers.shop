<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['carousel_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT image_url FROM home_carousel WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    
    if ($item) {
        $stmt = $pdo->prepare('DELETE FROM home_carousel WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($item['image_url'] && file_exists(__DIR__ . '/../templates/static/' . $item['image_url'])) {
            @unlink(__DIR__ . '/../templates/static/' . $item['image_url']);
        }
    }
    redirect('admin-carousel');
}

$items = $pdo->query('SELECT * FROM home_carousel ORDER BY sort_order ASC, id DESC')->fetchAll();
?>

<section>
    <h2 class="fw-bold mb-4">Manage Home Carousel</h2>
    <div class="d-flex gap-2 mb-4">
        <a href="<?= url('admin-add-carousel') ?>" class="btn btn-danger">Add Carousel Item</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white shadow-sm admin-table">
            <thead class="table-light"><tr><th>Image</th><th>Title</th><th>Status</th><th>Sort Order</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['image_url']): ?>
                            <img src="<?= asset($item['image_url']) ?>" alt="Carousel" style="height: 60px; width: auto; object-fit: cover;" class="rounded">
                        <?php else: ?>
                            <span class="text-muted">No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['title'] ?? '') ?></td>
                    <td>
                        <?php if ($item['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$item['sort_order'] ?></td>
                    <td class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin-edit-carousel', ['id' => $item['id']]) ?>">Edit</a>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="carousel_id" value="<?= (int)$item['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this slide?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$items): ?><div class="alert alert-info">No carousel items found.</div><?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
