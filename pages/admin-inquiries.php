<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

// Quick status update from list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quick_status') {
    verify_csrf();
    $inqId = (int)($_POST['inquiry_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Pending Review');
    if ($inqId > 0) {
        $stmt = $pdo->prepare('UPDATE inquiries SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $inqId]);
        $_SESSION['flash_msg'] = 'Enquiry #' . $inqId . ' status changed to ' . $status . '.';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('admin-inquiries');
}

// Quick delete enquiry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_inquiry') {
    verify_csrf();
    $inqId = (int)($_POST['inquiry_id'] ?? 0);
    if ($inqId > 0) {
        $pdo->prepare('DELETE FROM inquiry_items WHERE inquiry_id = :id')->execute(['id' => $inqId]);
        $pdo->prepare('DELETE FROM shipping_details WHERE inquiry_id = :id')->execute(['id' => $inqId]);
        $pdo->prepare('DELETE FROM inquiries WHERE id = :id')->execute(['id' => $inqId]);
        $_SESSION['flash_msg'] = 'Enquiry #' . $inqId . ' deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    }
    redirect('admin-inquiries');
}

$alertMsg = $_SESSION['flash_msg'] ?? null;
$alertType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Filter & search parameters
$searchQuery = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? 'all');

// Fetch summary metrics
$totalInquiriesCount = (int)$pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Pending Review'")->fetchColumn();
$processingCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Processing'")->fetchColumn();
$completedCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Completed'")->fetchColumn();
$totalEstimateRevenue = (float)$pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM inquiries')->fetchColumn();

// Base query for inquiries
$sql = 'SELECT inquiries.*, 
               users.name AS user_name, users.phone, 
               shipping_details.shipping_name, shipping_details.shipping_address_1, shipping_details.shipping_address_2, 
               shipping_details.city, shipping_details.pincode, shipping_details.district, shipping_details.state, 
               shipping_details.landmark, shipping_details.contact_number, shipping_details.alternate_contact_number, 
               shipping_details.whatsapp_number, shipping_details.customer_email, 
               inquiry_items.id AS item_id, inquiry_items.quantity, inquiry_items.price_at_booking, 
               products.name AS product_name, products.tamil_name
        FROM inquiries 
        LEFT JOIN users ON users.id = inquiries.user_id 
        LEFT JOIN shipping_details ON shipping_details.inquiry_id = inquiries.id 
        LEFT JOIN inquiry_items ON inquiry_items.inquiry_id = inquiries.id 
        LEFT JOIN products ON products.id = inquiry_items.product_id ';

$where = [];
$params = [];

if ($filterStatus !== 'all' && $filterStatus !== '') {
    $where[] = 'inquiries.status = :status';
    $params['status'] = $filterStatus;
}

if ($searchQuery !== '') {
    $where[] = '(shipping_details.shipping_name LIKE :q OR users.name LIKE :q OR shipping_details.contact_number LIKE :q OR users.phone LIKE :q OR inquiries.id = :qid)';
    $params['q'] = '%' . $searchQuery . '%';
    $params['qid'] = is_numeric($searchQuery) ? (int)$searchQuery : 0;
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY inquiries.created_at DESC, inquiry_items.id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$inquiryGroups = [];
foreach ($rows as $row) {
    $inquiryId = (int)$row['id'];
    if (!isset($inquiryGroups[$inquiryId])) {
        $inquiryGroups[$inquiryId] = $row;
        $inquiryGroups[$inquiryId]['items'] = [];
    }
    if ($row['product_name'] !== null) {
        $inquiryGroups[$inquiryId]['items'][] = [
            'item_id' => $row['item_id'],
            'product_name' => $row['product_name'],
            'tamil_name' => $row['tamil_name'],
            'quantity' => (int)$row['quantity'],
            'price_at_booking' => (float)$row['price_at_booking']
        ];
    }
}
?>

<section class="admin-inquiries-page">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 rounded">Customer Enquiries</span>
                <span class="text-muted small">Orders & Product Details Management</span>
            </div>
            <h2 class="fw-bold mb-0 text-dark">Customer Enquiries</h2>
            <p class="text-muted small mb-0">View customer requests, edit ordered product details, adjust prices & quantities, and manage delivery status.</p>
        </div>
        <div>
            <span class="text-muted small me-2">Total Estimate Value:</span>
            <span class="fs-4 fw-bold text-success">₹<?= number_format($totalEstimateRevenue, 2) ?></span>
        </div>
    </div>

    <?php if ($alertMsg): ?>
        <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div><?= htmlspecialchars($alertMsg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-secondary h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Total Enquiries</span>
                        <h3 class="fw-bold mb-0 mt-1"><?= number_format($totalInquiriesCount) ?></h3>
                    </div>
                    <div class="bg-secondary-subtle text-secondary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-inbox fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Pending Review</span>
                        <h3 class="fw-bold mb-0 mt-1 text-warning"><?= number_format($pendingCount) ?></h3>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-clock fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Processing</span>
                        <h3 class="fw-bold mb-0 mt-1 text-info"><?= number_format($processingCount) ?></h3>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-boxes-packing fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Completed</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success"><?= number_format($completedCount) ?></h3>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('admin-inquiries') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="admin-inquiries">

                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Search customer name, phone, enquiry #ID..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Status: All Enquiries</option>
                        <option value="Pending Review" <?= $filterStatus === 'Pending Review' ? 'selected' : '' ?>>Pending Review</option>
                        <option value="Processing" <?= $filterStatus === 'Processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $filterStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-semibold">Filter</button>
                    <?php if ($searchQuery !== '' || $filterStatus !== 'all'): ?>
                        <a href="<?= url('admin-inquiries') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Enquiries Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                Enquiries List
                <span class="badge bg-secondary-subtle text-secondary ms-2 fw-normal fs-6"><?= count($inquiryGroups) ?> enquiries shown</span>
            </h5>
            <small class="text-muted">Click <strong>"Change Products / Edit"</strong> on any order to modify products</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead class="table-light text-uppercase small text-muted" style="letter-spacing: 0.5px;">
                    <tr>
                        <th style="width: 240px;">Customer Info</th>
                        <th style="min-width: 320px;">Shopping & Product Details</th>
                        <th style="min-width: 260px;">Delivery Address</th>
                        <th style="width: 220px;">Status & Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($inquiryGroups): ?>
                    <?php foreach ($inquiryGroups as $inquiry): ?>
                        <?php 
                        $custName = $inquiry['shipping_name'] ?: $inquiry['user_name'] ?: 'Customer';
                        $phone = $inquiry['contact_number'] ?: $inquiry['user_phone'] ?: '';
                        $cleanPhone = preg_replace('/\D+/', '', $phone);
                        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                        ?>
                        <tr>
                            <!-- Customer Details Column -->
                            <td class="align-top">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-danger fw-bold">#<?= (int)$inquiry['id'] ?></span>
                                    <strong class="text-dark fs-6"><?= htmlspecialchars($custName) ?></strong>
                                </div>

                                <?php if ($phone): ?>
                                    <div class="small text-muted mb-1">
                                        <i class="fa-solid fa-phone me-1 text-primary"></i>
                                        <a href="tel:<?= htmlspecialchars($phone) ?>" class="text-decoration-none text-dark fw-semibold"><?= htmlspecialchars($phone) ?></a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($inquiry['customer_email'])): ?>
                                    <div class="small text-muted mb-1 text-truncate" style="max-width: 210px;" title="<?= htmlspecialchars($inquiry['customer_email']) ?>">
                                        <i class="fa-solid fa-envelope me-1 text-muted"></i><?= htmlspecialchars($inquiry['customer_email']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="text-muted small mb-2">
                                    <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($inquiry['created_at']) ?>
                                </div>

                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <a class="btn btn-sm btn-outline-danger" href="<?= url('admin-download-inquiry', ['id' => (int)$inquiry['id']]) ?>" title="Download Invoice / Estimate PDF">
                                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                    </a>
                                    <?php if ($cleanPhone): ?>
                                        <a class="btn btn-sm btn-outline-success" href="https://wa.me/<?= htmlspecialchars($cleanPhone) ?>?text=Hello%20<?= urlencode($custName) ?>%2C%20regarding%20your%20Shantini%20Crackers%20enquiry%20%23<?= (int)$inquiry['id'] ?>" target="_blank" rel="noopener" title="Chat on WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Shopping & Product Details Column with CRUD Edit Button -->
                            <td class="align-top">
                                <div class="p-2 rounded bg-light border mb-2">
                                    <?php if ($inquiry['items']): ?>
                                        <ul class="list-unstyled mb-2 small">
                                            <?php foreach ($inquiry['items'] as $item): ?>
                                                <?php $lineTotal = (float)$item['price_at_booking'] * (int)$item['quantity']; ?>
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light-subtle">
                                                    <div>
                                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($item['product_name']) ?></span>
                                                        <?php if (!empty($item['tamil_name'])): ?>
                                                            <small class="text-primary">(<?= htmlspecialchars($item['tamil_name']) ?>)</small>
                                                        <?php endif; ?>
                                                        <span class="badge bg-secondary-subtle text-secondary ms-1">x<?= (int)$item['quantity'] ?></span>
                                                    </div>
                                                    <div class="text-end fw-medium text-dark ps-2">
                                                        ₹<?= number_format($lineTotal, 2) ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No products in this enquiry.</span>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center pt-1">
                                        <span class="fw-bold text-dark small">Total Estimate Amount:</span>
                                        <span class="fw-bold text-success fs-6">₹<?= number_format((float)$inquiry['total_amount'], 2) ?></span>
                                    </div>
                                </div>

                                <!-- PRIMARY CRUD BUTTON FOR CHANGING PRODUCT DETAILS OF CUSTOMER -->
                                <a href="<?= url('admin-edit-inquiry', ['id' => (int)$inquiry['id']]) ?>" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 shadow-sm fw-semibold">
                                    <i class="fa-solid fa-pen-to-square"></i> Change Products / Edit Order (CRUD)
                                </a>
                            </td>

                            <!-- Delivery Address Column -->
                            <td class="align-top small">
                                <div class="text-dark">
                                    <?= htmlspecialchars($inquiry['shipping_address_1'] ?? '') ?>
                                    <?php if (!empty($inquiry['shipping_address_2'])): ?>, <?= htmlspecialchars($inquiry['shipping_address_2']) ?><?php endif; ?>
                                </div>
                                <div class="text-muted mt-1">
                                    <strong><?= htmlspecialchars($inquiry['city'] ?? '') ?></strong>, 
                                    <?= htmlspecialchars($inquiry['district'] ?? '') ?>, 
                                    <?= htmlspecialchars($inquiry['state'] ?? '') ?> - <strong><?= htmlspecialchars($inquiry['pincode'] ?? '') ?></strong>
                                </div>
                                <?php if (!empty($inquiry['landmark'])): ?>
                                    <div class="text-muted mt-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?= htmlspecialchars($inquiry['landmark']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['alternate_contact_number'])): ?>
                                    <div class="text-muted mt-1"><i class="fa-solid fa-phone-volume me-1"></i>Alt: <?= htmlspecialchars($inquiry['alternate_contact_number']) ?></div>
                                <?php endif; ?>

                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    <?php if (!empty($inquiry['is_paid'])): ?>
                                        <span class="badge bg-success-subtle text-success small"><i class="fa-solid fa-check me-1"></i>Paid</span>
                                    <?php endif; ?>
                                    <?php if (!empty($inquiry['is_dispatched'])): ?>
                                        <span class="badge bg-primary-subtle text-primary small"><i class="fa-solid fa-truck-fast me-1"></i>Dispatched</span>
                                    <?php endif; ?>
                                    <?php if (!empty($inquiry['is_delivered'])): ?>
                                        <span class="badge bg-info-subtle text-info small"><i class="fa-solid fa-house-circle-check me-1"></i>Delivered</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Status & Quick Action Column -->
                            <td class="align-top">
                                <form method="POST" class="mb-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="quick_status">
                                    <input type="hidden" name="inquiry_id" value="<?= (int)$inquiry['id'] ?>">
                                    <div class="input-group input-group-sm mb-2">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="Pending Review" <?= $inquiry['status'] === 'Pending Review' ? 'selected' : '' ?>>Pending Review</option>
                                            <option value="Processing" <?= $inquiry['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="Completed" <?= $inquiry['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $inquiry['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button class="btn btn-outline-dark" type="submit" title="Save Status">Save</button>
                                    </div>
                                </form>

                                <div class="d-flex flex-column gap-1">
                                    <a href="<?= url('admin-edit-inquiry', ['id' => (int)$inquiry['id']]) ?>" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fa-solid fa-sliders me-1"></i> Manage Order Details
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Delete enquiry #<?= (int)$inquiry['id'] ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_inquiry">
                                        <input type="hidden" name="inquiry_id" value="<?= (int)$inquiry['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none small w-100 mt-1">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete Enquiry
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-envelope-open fs-1 opacity-50 mb-3 d-block"></i>
                            <h5>No customer enquiries found</h5>
                            <p class="small mb-0">Try clearing your search query or status filter.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
