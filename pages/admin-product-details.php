<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$alertMessage = null;
$alertType = 'success';

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $deleteId = (int)($_POST['product_id'] ?? 0);
    if ($deleteId > 0) {
        $imgStmt = $pdo->prepare('SELECT image_url FROM products WHERE id = :id');
        $imgStmt->execute(['id' => $deleteId]);
        $prodImg = $imgStmt->fetchColumn();

        $delStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $delStmt->execute(['id' => $deleteId]);

        if ($prodImg && str_starts_with($prodImg, 'uploads/') && file_exists(__DIR__ . '/../templates/static/' . $prodImg)) {
            @unlink(__DIR__ . '/../templates/static/' . $prodImg);
        }

        $_SESSION['flash_msg'] = 'Product details deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('admin-product-details');
}

// Flash messages
if (!empty($_SESSION['flash_msg'])) {
    $alertMessage = $_SESSION['flash_msg'];
    $alertType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
} elseif (!empty($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $alertMessage = 'New product details added successfully.';
    } elseif ($_GET['msg'] === 'updated') {
        $alertMessage = 'Product details updated successfully.';
    }
}

// Fetch categories and brands for filters
$categories = $pdo->query('SELECT id, name, discount_percent FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY name')->fetchAll();

// Quick Overview Stats
$totalCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$withTamilCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE tamil_name IS NOT NULL AND TRIM(tamil_name) != ''")->fetchColumn();
$withDescCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE description IS NOT NULL AND TRIM(description) != ''")->fetchColumn();
$inStockCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_stock = 1')->fetchColumn();

// Filter parameters
$searchQuery = trim($_GET['q'] ?? '');
$filterCategory = (int)($_GET['category_id'] ?? 0);
$filterTamil = $_GET['tamil'] ?? 'all';
$filterStock = $_GET['stock'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'id_desc';

$whereClauses = [];
$params = [];

if ($searchQuery !== '') {
    $whereClauses[] = '(products.name LIKE :q OR products.tamil_name LIKE :q OR products.id = :qid)';
    $params['q'] = '%' . $searchQuery . '%';
    $params['qid'] = is_numeric($searchQuery) ? (int)$searchQuery : 0;
}

if ($filterCategory > 0) {
    $whereClauses[] = 'products.category_id = :cat_id';
    $params['cat_id'] = $filterCategory;
}

if ($filterTamil === 'with') {
    $whereClauses[] = "(products.tamil_name IS NOT NULL AND TRIM(products.tamil_name) != '')";
} elseif ($filterTamil === 'missing') {
    $whereClauses[] = "(products.tamil_name IS NULL OR TRIM(products.tamil_name) = '')";
}

if ($filterStock === 'in') {
    $whereClauses[] = 'products.is_stock = 1';
} elseif ($filterStock === 'out') {
    $whereClauses[] = '(products.is_stock = 0 OR products.is_stock IS NULL)';
}

$whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Sorting
$orderSql = 'ORDER BY products.id DESC';
if ($sortBy === 'id_asc') {
    $orderSql = 'ORDER BY products.id ASC';
} elseif ($sortBy === 'name_asc') {
    $orderSql = 'ORDER BY products.name ASC';
} elseif ($sortBy === 'price_asc') {
    $orderSql = 'ORDER BY products.discount_price ASC';
} elseif ($sortBy === 'price_desc') {
    $orderSql = 'ORDER BY products.discount_price DESC';
}

// Pagination
$limit = 15;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

// Count filtered
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products LEFT JOIN categories ON categories.id = products.category_id $whereSql");
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($filteredTotal / $limit));

// Query items
$sql = "SELECT products.*, categories.name AS category_name, categories.discount_percent, brands.name AS brand_name 
        FROM products 
        LEFT JOIN categories ON categories.id = products.category_id 
        LEFT JOIN brands ON brands.id = products.brand_id 
        $whereSql 
        $orderSql 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$productsList = $stmt->fetchAll();

// Helper to preserve filters in links
function build_filter_url($newPage = 1) {
    $query = $_GET;
    $query['p'] = $newPage;
    return url('admin-product-details', $query);
}
?>

<section class="admin-product-details-page">
    <!-- Header with Breadcrumb & Action Button -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1 rounded">Product Details Manager</span>
                <span class="text-muted small">Catalog Details & Tamil Translations</span>
            </div>
            <h2 class="fw-bold mb-0 text-dark">Product Details CRUD</h2>
            <p class="text-muted small mb-0">Create, view, edit descriptions, Tamil names, images, prices, and stock status.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin-add-product-detail') ?>" class="btn btn-danger d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm fw-semibold">
                <i class="fa-solid fa-plus-circle"></i> Add Product Details
            </a>
        </div>
    </div>

    <?php if ($alertMessage): ?>
        <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div><?= htmlspecialchars($alertMessage) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Total Catalog</span>
                        <h3 class="fw-bold mb-0 mt-1"><?= number_format($totalCount) ?></h3>
                    </div>
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-boxes-stacked fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Tamil Names</span>
                        <h3 class="fw-bold mb-0 mt-1 text-primary"><?= number_format($withTamilCount) ?></h3>
                        <small class="text-muted"><?= $totalCount > 0 ? round(($withTamilCount / $totalCount) * 100) : 0 ?>% translated</small>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-language fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">With Description</span>
                        <h3 class="fw-bold mb-0 mt-1 text-info"><?= number_format($withDescCount) ?></h3>
                        <small class="text-muted"><?= $totalCount > 0 ? round(($withDescCount / $totalCount) * 100) : 0 ?>% detailed</small>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-align-left fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">In Stock</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success"><?= number_format($inStockCount) ?></h3>
                        <small class="text-muted"><?= number_format($totalCount - $inStockCount) ?> out of stock</small>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin-product-details') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="admin-product-details">
                
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Search by name, Tamil name, ID..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $filterCategory === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?> (<?= htmlspecialchars($cat['discount_percent']) ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="tamil" class="form-select form-select-sm">
                        <option value="all" <?= $filterTamil === 'all' ? 'selected' : '' ?>>Tamil: All</option>
                        <option value="with" <?= $filterTamil === 'with' ? 'selected' : '' ?>>With Tamil Name</option>
                        <option value="missing" <?= $filterTamil === 'missing' ? 'selected' : '' ?>>Missing Tamil Name</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="stock" class="form-select form-select-sm">
                        <option value="all" <?= $filterStock === 'all' ? 'selected' : '' ?>>Stock: All</option>
                        <option value="in" <?= $filterStock === 'in' ? 'selected' : '' ?>>In Stock Only</option>
                        <option value="out" <?= $filterStock === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="sort" class="form-select form-select-sm">
                        <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>Newest First</option>
                        <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">Filter</button>
                    <?php if ($searchQuery !== '' || $filterCategory > 0 || $filterTamil !== 'all' || $filterStock !== 'all' || $sortBy !== 'id_desc'): ?>
                        <a href="<?= url('admin-product-details') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Details Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-dark">
                Product Details List
                <span class="badge bg-secondary-subtle text-secondary ms-2 fw-normal fs-6"><?= number_format($filteredTotal) ?> items found</span>
            </h5>
            <small class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?></small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead class="table-light text-uppercase small text-muted" style="letter-spacing: 0.5px;">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th style="width: 70px;">Image</th>
                        <th>Product Details</th>
                        <th>Category & Brand</th>
                        <th>Pricing</th>
                        <th style="width: 110px;">Stock</th>
                        <th>Description</th>
                        <th style="width: 150px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($productsList): ?>
                    <?php foreach ($productsList as $p): ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= (int)$p['id'] ?></td>
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?= asset($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover; border: 1px solid #dee2e6;">
                                <?php else: ?>
                                    <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.25rem; border: 1px dashed #ced4da;">
                                        🧨
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if (!empty($p['tamil_name'])): ?>
                                    <div class="text-primary small fw-semibold mt-1">
                                        <i class="fa-solid fa-language me-1 text-muted"></i><?= htmlspecialchars($p['tamil_name']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis small mt-1">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>No Tamil Name
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></span></div>
                                <?php if (!empty($p['brand_name'])): ?>
                                    <small class="text-muted d-block mt-1"><i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars($p['brand_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-success">₹<?= number_format((float)$p['discount_price'], 2) ?></div>
                                <?php if (!empty($p['actual_price']) && $p['actual_price'] != $p['discount_price']): ?>
                                    <small class="text-muted text-decoration-line-through">₹<?= number_format((float)$p['actual_price'], 2) ?></small>
                                    <span class="badge bg-danger-subtle text-danger small"><?= (int)$p['discount_percent'] ?>% off</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['is_stock'])): ?>
                                    <span class="badge bg-success-subtle text-success px-2 py-1"><i class="fa-solid fa-check me-1"></i>In Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="fa-solid fa-xmark me-1"></i>Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['description'])): ?>
                                    <div class="small text-muted" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($p['description']) ?>">
                                        <?= htmlspecialchars($p['description']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Default description</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('product-detail', ['id' => $p['id']]) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View in Store">
                                        <i class="fa-solid fa-external-link"></i>
                                    </a>
                                    <a href="<?= url('admin-edit-product-detail', ['id' => $p['id']]) ?>" class="btn btn-sm btn-primary" title="Edit Product Details">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fa-solid fa-box-open fs-1 mb-3 text-secondary opacity-50"></i>
                                <h5>No product details found</h5>
                                <p class="small mb-3">Try adjusting your search criteria or add a new product detail.</p>
                                <a href="<?= url('admin-add-product-detail') ?>" class="btn btn-sm btn-danger">Add Product Detail</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap justify-content-between align-items-center">
                <div class="text-muted small mb-2 mb-sm-0">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $filteredTotal) ?> of <?= $filteredTotal ?> products
                </div>
                <nav aria-label="Product details pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_filter_url($page - 1) ?>">Previous</a>
                        </li>
                        
                        <?php
                        $startP = max(1, $page - 2);
                        $endP = min($totalPages, $page + 2);
                        if ($startP > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . build_filter_url(1) . '">1</a></li>';
                            if ($startP > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($i = $startP; $i <= $endP; $i++):
                        ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= build_filter_url($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php
                        if ($endP < $totalPages) {
                            if ($endP < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="' . build_filter_url($totalPages) . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_filter_url($page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
