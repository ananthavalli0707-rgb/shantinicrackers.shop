<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => (int)($_POST['product_id'] ?? 0)]);
    redirect('admin-dashboard');
}

$limit = 10;
$p = max(1, (int)($_GET['p'] ?? 1));
$offset = ($p - 1) * $limit;

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalPages = max(1, ceil($totalProducts / $limit));

$stmt = $pdo->prepare('SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id ORDER BY products.id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$inquiryCount = (int)$pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
?>

<section>
    <h2 class="fw-bold mb-4">Admin Dashboard</h2>
    <div class="d-flex gap-2 mb-4">
        <a href="<?= url('admin-add-product') ?>" class="btn btn-danger">Add Product</a>
        <a href="<?= url('admin-inquiries') ?>" class="btn btn-outline-dark">Enquiries (<?= $inquiryCount ?>)</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle admin-table">
            <thead><tr><th>Product</th><th>Category</th><th>Actual</th><th>Estimate Price</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                    <td>₹<?= htmlspecialchars($product['actual_price']) ?></td>
                    <td>₹<?= htmlspecialchars($product['discount_price']) ?></td>
                    <td class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin-edit-product', ['id' => $product['id']]) ?>">Edit</a>
                        <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Product pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('admin-dashboard', ['p' => $p - 1]) ?>" tabindex="-1">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $p ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('admin-dashboard', ['p' => $i]) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $p >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('admin-dashboard', ['p' => $p + 1]) ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <?php if (!$products): ?><div class="alert alert-info">No products found.</div><?php endif; ?>
    <div class="row g-4 d-none">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h5>Products</h5>
                <p class="text-muted mb-0">Manage product list and pricing.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h5>Orders</h5>
                <p class="text-muted mb-0">Track customer estimate requests.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h5>Customers</h5>
                <p class="text-muted mb-0">Manage enquiries and account requests.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
