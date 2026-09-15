<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$categoryId = (int)($_GET['category_id'] ?? 0);
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$sql = 'SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id';
$params = [];
if ($categoryId > 0) {
    $sql .= ' WHERE products.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
$sql .= ' ORDER BY products.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<section>
    <h2 class="fw-bold mb-4">Category Products</h2>
    <form method="GET" class="mb-4 d-flex gap-2">
        <input type="hidden" name="page" value="admin-category-products">
        <select name="category_id" class="form-select" style="max-width: 320px"><option value="0">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-outline-dark">Filter</button>
    </form>
    <div class="table-responsive"><table class="table table-bordered admin-table"><thead><tr><th>Name</th><th>Category</th><th>Actual</th><th>Estimate</th><th></th></tr></thead><tbody>
    <?php foreach ($products as $product): ?><tr><td><?= htmlspecialchars($product['name']) ?></td><td><?= htmlspecialchars($product['category_name'] ?? '') ?></td><td>₹<?= htmlspecialchars($product['actual_price']) ?></td><td>₹<?= htmlspecialchars($product['discount_price']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= url('admin-edit-product', ['id' => $product['id']]) ?>">Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
