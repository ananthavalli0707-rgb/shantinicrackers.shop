<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

$inquiryId = (int)($_GET['id'] ?? 0);
if ($inquiryId <= 0) {
    redirect('admin-inquiries');
}

// Function to recalculate inquiry total
function recalculate_inquiry_total(PDO $pdo, int $inquiryId): float {
    $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity * price_at_booking), 0) FROM inquiry_items WHERE inquiry_id = :id');
    $sumStmt->execute(['id' => $inquiryId]);
    $newTotal = (float)$sumStmt->fetchColumn();

    $updStmt = $pdo->prepare('UPDATE inquiries SET total_amount = :total WHERE id = :id');
    $updStmt->execute(['total' => $newTotal, 'id' => $inquiryId]);

    return $newTotal;
}

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // 1. ADD PRODUCT TO INQUIRY
    if ($action === 'add_product') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $customPrice = trim($_POST['price_at_booking'] ?? '');

        if ($productId > 0) {
            // Fetch product details
            $prodStmt = $pdo->prepare('SELECT name, discount_price FROM products WHERE id = :id');
            $prodStmt->execute(['id' => $productId]);
            $prod = $prodStmt->fetch();

            if ($prod) {
                $unitPrice = ($customPrice !== '' && is_numeric($customPrice)) ? (float)$customPrice : (float)$prod['discount_price'];

                // Check if product already exists in this inquiry
                $chkStmt = $pdo->prepare('SELECT id, quantity FROM inquiry_items WHERE inquiry_id = :inq_id AND product_id = :prod_id LIMIT 1');
                $chkStmt->execute(['inq_id' => $inquiryId, 'prod_id' => $productId]);
                $existing = $chkStmt->fetch();

                if ($existing) {
                    $newQty = (int)$existing['quantity'] + $quantity;
                    $updItem = $pdo->prepare('UPDATE inquiry_items SET quantity = :qty, price_at_booking = :price WHERE id = :id');
                    $updItem->execute(['qty' => $newQty, 'price' => $unitPrice, 'id' => $existing['id']]);
                } else {
                    $insItem = $pdo->prepare('INSERT INTO inquiry_items (inquiry_id, product_id, quantity, price_at_booking) VALUES (:inq_id, :prod_id, :qty, :price)');
                    $insItem->execute(['inq_id' => $inquiryId, 'prod_id' => $productId, 'qty' => $quantity, 'price' => $unitPrice]);
                }

                recalculate_inquiry_total($pdo, $inquiryId);
                $_SESSION['flash_msg'] = 'Added "' . $prod['name'] . '" (x' . $quantity . ') to customer enquiry.';
                $_SESSION['flash_type'] = 'success';
            }
        }
        redirect('admin-edit-inquiry', ['id' => $inquiryId]);
    }

    // 2. UPDATE PRODUCT ITEM IN INQUIRY (Edit quantity or price)
    if ($action === 'update_product_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $price = max(0, (float)($_POST['price_at_booking'] ?? 0));

        if ($itemId > 0) {
            $updItem = $pdo->prepare('UPDATE inquiry_items SET quantity = :qty, price_at_booking = :price WHERE id = :id AND inquiry_id = :inq_id');
            $updItem->execute(['qty' => $quantity, 'price' => $price, 'id' => $itemId, 'inq_id' => $inquiryId]);
            recalculate_inquiry_total($pdo, $inquiryId);

            $_SESSION['flash_msg'] = 'Product item details updated successfully.';
            $_SESSION['flash_type'] = 'success';
        }
        redirect('admin-edit-inquiry', ['id' => $inquiryId]);
    }

    // 3. DELETE PRODUCT FROM INQUIRY
    if ($action === 'delete_product_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            $delItem = $pdo->prepare('DELETE FROM inquiry_items WHERE id = :id AND inquiry_id = :inq_id');
            $delItem->execute(['id' => $itemId, 'inq_id' => $inquiryId]);
            recalculate_inquiry_total($pdo, $inquiryId);

            $_SESSION['flash_msg'] = 'Product removed from customer enquiry.';
            $_SESSION['flash_type'] = 'warning';
        }
        redirect('admin-edit-inquiry', ['id' => $inquiryId]);
    }

    // 4. UPDATE CUSTOMER & DELIVERY DETAILS
    if ($action === 'update_customer_details') {
        $status = trim($_POST['status'] ?? 'Pending Review');
        $isPaid = !empty($_POST['is_paid']) ? 1 : 0;
        $isDispatched = !empty($_POST['is_dispatched']) ? 1 : 0;
        $isDelivered = !empty($_POST['is_delivered']) ? 1 : 0;

        $inqUpd = $pdo->prepare('UPDATE inquiries SET status = :status, is_paid = :is_paid, is_dispatched = :is_dispatched, is_delivered = :is_delivered WHERE id = :id');
        $inqUpd->execute([
            'status' => $status,
            'is_paid' => $isPaid,
            'is_dispatched' => $isDispatched,
            'is_delivered' => $isDelivered,
            'id' => $inquiryId
        ]);

        // Update shipping details
        $shippingName = trim($_POST['shipping_name'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $alternateNumber = trim($_POST['alternate_contact_number'] ?? '') ?: null;
        $whatsappNumber = trim($_POST['whatsapp_number'] ?? '') ?: null;
        $customerEmail = trim($_POST['customer_email'] ?? '') ?: null;
        $address1 = trim($_POST['shipping_address_1'] ?? '');
        $address2 = trim($_POST['shipping_address_2'] ?? '') ?: null;
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $state = trim($_POST['state'] ?? 'Tamil Nadu');
        $pincode = trim($_POST['pincode'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '') ?: null;

        // Check if shipping details record exists
        $chkShip = $pdo->prepare('SELECT id FROM shipping_details WHERE inquiry_id = :id');
        $chkShip->execute(['id' => $inquiryId]);
        if ($chkShip->fetch()) {
            $shipUpd = $pdo->prepare('UPDATE shipping_details SET 
                shipping_name = :name,
                contact_number = :phone,
                alternate_contact_number = :alt_phone,
                whatsapp_number = :whatsapp,
                customer_email = :email,
                shipping_address_1 = :addr1,
                shipping_address_2 = :addr2,
                city = :city,
                district = :district,
                state = :state,
                pincode = :pincode,
                landmark = :landmark
                WHERE inquiry_id = :id');
            $shipUpd->execute([
                'name' => $shippingName,
                'phone' => $contactNumber,
                'alt_phone' => $alternateNumber,
                'whatsapp' => $whatsappNumber,
                'email' => $customerEmail,
                'addr1' => $address1,
                'addr2' => $address2,
                'city' => $city,
                'district' => $district,
                'state' => $state,
                'pincode' => $pincode,
                'landmark' => $landmark,
                'id' => $inquiryId
            ]);
        } else {
            $shipIns = $pdo->prepare('INSERT INTO shipping_details (inquiry_id, shipping_name, contact_number, alternate_contact_number, whatsapp_number, customer_email, shipping_address_1, shipping_address_2, city, district, state, pincode, landmark) VALUES (:id, :name, :phone, :alt_phone, :whatsapp, :email, :addr1, :addr2, :city, :district, :state, :pincode, :landmark)');
            $shipIns->execute([
                'id' => $inquiryId,
                'name' => $shippingName,
                'phone' => $contactNumber,
                'alt_phone' => $alternateNumber,
                'whatsapp' => $whatsappNumber,
                'email' => $customerEmail,
                'addr1' => $address1,
                'addr2' => $address2,
                'city' => $city,
                'district' => $district,
                'state' => $state,
                'pincode' => $pincode,
                'landmark' => $landmark
            ]);
        }

        $_SESSION['flash_msg'] = 'Customer delivery information and order status updated successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('admin-edit-inquiry', ['id' => $inquiryId]);
    }

    // 5. DELETE ENTIRE INQUIRY
    if ($action === 'delete_inquiry') {
        $pdo->prepare('DELETE FROM inquiry_items WHERE inquiry_id = :id')->execute(['id' => $inquiryId]);
        $pdo->prepare('DELETE FROM shipping_details WHERE inquiry_id = :id')->execute(['id' => $inquiryId]);
        $pdo->prepare('DELETE FROM inquiries WHERE id = :id')->execute(['id' => $inquiryId]);

        $_SESSION['flash_msg'] = 'Enquiry #' . $inquiryId . ' deleted successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('admin-inquiries');
    }
}

// Fetch enquiry and customer details
$inqStmt = $pdo->prepare('SELECT inquiries.*, users.name AS user_name, users.phone AS user_phone, users.email AS user_email,
                          shipping_details.shipping_name, shipping_details.shipping_address_1, shipping_details.shipping_address_2,
                          shipping_details.city, shipping_details.pincode, shipping_details.district, shipping_details.state,
                          shipping_details.landmark, shipping_details.contact_number, shipping_details.alternate_contact_number,
                          shipping_details.whatsapp_number, shipping_details.customer_email
                          FROM inquiries 
                          LEFT JOIN users ON users.id = inquiries.user_id 
                          LEFT JOIN shipping_details ON shipping_details.inquiry_id = inquiries.id 
                          WHERE inquiries.id = :id');
$inqStmt->execute(['id' => $inquiryId]);
$inquiry = $inqStmt->fetch();

if (!$inquiry) {
    $_SESSION['flash_msg'] = 'Enquiry not found.';
    $_SESSION['flash_type'] = 'danger';
    redirect('admin-inquiries');
}

// Fetch enquiry product items
$itemsStmt = $pdo->prepare('SELECT inquiry_items.*, products.name AS product_name, products.tamil_name, products.image_url, 
                            products.actual_price AS current_actual_price, products.discount_price AS current_discount_price,
                            categories.name AS category_name
                            FROM inquiry_items 
                            LEFT JOIN products ON products.id = inquiry_items.product_id 
                            LEFT JOIN categories ON categories.id = products.category_id 
                            WHERE inquiry_items.inquiry_id = :id 
                            ORDER BY inquiry_items.id ASC');
$itemsStmt->execute(['id' => $inquiryId]);
$inquiryItems = $itemsStmt->fetchAll();

// Fetch all catalog products for "Add Product" dropdown
$catalogProducts = $pdo->query('SELECT products.id, products.name, products.tamil_name, products.actual_price, products.discount_price, categories.name AS category_name 
                                FROM products 
                                LEFT JOIN categories ON categories.id = products.category_id 
                                ORDER BY categories.name ASC, products.name ASC')->fetchAll();

// Pre-create price map for JS
$productPrices = [];
foreach ($catalogProducts as $cp) {
    $productPrices[$cp['id']] = (float)$cp['discount_price'];
}

$alertMsg = $_SESSION['flash_msg'] ?? null;
$alertType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>

<section class="admin-edit-inquiry-page pb-5">
    <!-- Breadcrumb & Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= url('admin-inquiries') ?>" class="text-decoration-none text-muted small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Enquiries
                </a>
                <span class="text-muted">•</span>
                <span class="badge bg-danger-subtle text-danger fw-bold">Customer Enquiry #<?= (int)$inquiry['id'] ?></span>
            </div>
            <h2 class="fw-bold mb-0 text-dark">
                Manage Enquiry & Edit Products
            </h2>
            <p class="text-muted small mb-0">
                Customer: <strong><?= htmlspecialchars($inquiry['shipping_name'] ?: $inquiry['user_name'] ?: 'Customer') ?></strong>
                • Phone: <strong><?= htmlspecialchars($inquiry['contact_number'] ?: $inquiry['user_phone'] ?: 'N/A') ?></strong>
                • Date: <strong><?= htmlspecialchars($inquiry['created_at']) ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('admin-download-inquiry', ['id' => $inquiryId]) ?>" class="btn btn-outline-danger d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i> Download PDF
            </a>
            <a href="<?= url('admin-inquiries') ?>" class="btn btn-outline-secondary">
                Back to List
            </a>
        </div>
    </div>

    <?php if ($alertMsg): ?>
        <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div><?= htmlspecialchars($alertMsg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: CRUD Operations for Products Ordered in Enquiry -->
        <div class="col-lg-8">
            <!-- Ordered Products Table Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-cart-shopping me-2 text-danger"></i>Ordered Products (<?= count($inquiryItems) ?> items)
                        </h5>
                        <small class="text-muted">Modify quantities, adjust prices, or remove products from this customer's inquiry.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Total Estimate:</span>
                        <span class="fs-5 fw-bold text-success">₹<?= number_format((float)$inquiry['total_amount'], 2) ?></span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 admin-table">
                        <thead class="table-light text-uppercase small text-muted" style="letter-spacing: 0.5px;">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 55px;">Image</th>
                                <th>Product Details</th>
                                <th style="width: 120px;">Unit Price (₹)</th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 110px;">Subtotal (₹)</th>
                                <th style="width: 130px;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($inquiryItems): ?>
                            <?php foreach ($inquiryItems as $idx => $item): ?>
                                <?php $itemSubtotal = (float)$item['price_at_booking'] * (int)$item['quantity']; ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                                    <td>
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= asset($item['image_url']) ?>" alt="" class="rounded shadow-sm" style="width: 42px; height: 42px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 1.1rem; border: 1px dashed #ced4da;">
                                                🧨
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['product_name'] ?? 'Custom Product') ?></div>
                                        <?php if (!empty($item['tamil_name'])): ?>
                                            <small class="text-primary fw-semibold"><i class="fa-solid fa-language me-1"></i><?= htmlspecialchars($item['tamil_name']) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($item['category_name'])): ?>
                                            <span class="badge bg-light text-dark border ms-1 small"><?= htmlspecialchars($item['category_name']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" id="form-item-<?= (int)$item['id'] ?>" class="d-flex align-items-center">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="update_product_item">
                                            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                            <input type="number" name="price_at_booking" class="form-control form-control-sm text-end" step="0.01" min="0" value="<?= htmlspecialchars($item['price_at_booking']) ?>" style="width: 95px;" required title="Unit Price at Booking">
                                    </td>
                                    <td>
                                            <input type="number" name="quantity" class="form-control form-control-sm text-center" min="1" value="<?= (int)$item['quantity'] ?>" style="width: 70px;" required title="Quantity">
                                        </form>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">₹<?= number_format($itemSubtotal, 2) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="submit" form="form-item-<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-success" title="Save changes to this item">
                                                <i class="fa-solid fa-check"></i> Save
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($item['product_name'] ?? 'product')) ?> from this customer order?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_product_item">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove item">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td colspan="5" class="text-end fw-bold text-dark py-3">Total Amount:</td>
                                <td colspan="2" class="fw-bold fs-5 text-success py-3">₹<?= number_format((float)$inquiry['total_amount'], 2) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-cart-arrow-down fs-1 opacity-50 mb-3 d-block"></i>
                                    <h6>No products currently in this enquiry.</h6>
                                    <p class="small">Use the form below to add products for this customer.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADD PRODUCT TO ENQUIRY CARD -->
            <div class="card border-0 shadow-sm rounded-3 bg-white p-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                    <i class="fa-solid fa-cart-plus me-2 text-primary"></i>Add Product to this Customer's Enquiry
                </h5>
                <form method="POST" id="addProductForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_product">
                    
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Select Product from Catalog <span class="text-danger">*</span></label>
                            <select name="product_id" id="addProductSelect" class="form-select" required>
                                <option value="">-- Choose Product to Add --</option>
                                <?php 
                                $currentCatGroup = '';
                                foreach ($catalogProducts as $cp): 
                                    if ($currentCatGroup !== ($cp['category_name'] ?? 'Other')):
                                        if ($currentCatGroup !== '') echo '</optgroup>';
                                        $currentCatGroup = $cp['category_name'] ?? 'Other';
                                        echo '<optgroup label="' . htmlspecialchars($currentCatGroup) . '">';
                                    endif;
                                ?>
                                    <option value="<?= (int)$cp['id'] ?>" data-price="<?= htmlspecialchars($cp['discount_price']) ?>">
                                        <?= htmlspecialchars($cp['name']) ?> <?= !empty($cp['tamil_name']) ? ' (' . htmlspecialchars($cp['tamil_name']) . ')' : '' ?> - ₹<?= htmlspecialchars($cp['discount_price']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($currentCatGroup !== '') echo '</optgroup>'; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="addQuantityInput" class="form-control" value="1" min="1" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Unit Price (₹)</label>
                            <input type="number" name="price_at_booking" id="addPriceInput" class="form-control" step="0.01" min="0" placeholder="Auto">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        * Unit Price automatically fills with the product's estimate price. You can override it with a custom discount price if needed.
                    </small>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: Customer Details, Delivery Info & Order Status -->
        <div class="col-lg-4">
            <!-- Order Status & Progress Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white p-4 mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i>Enquiry Status & Fulfilment
                </h5>

                <form method="POST" id="statusForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_customer_details">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Order Status</label>
                        <select name="status" class="form-select">
                            <option value="Pending Review" <?= $inquiry['status'] === 'Pending Review' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="Processing" <?= $inquiry['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="Completed" <?= $inquiry['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $inquiry['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <span class="fw-semibold small d-block mb-2 text-dark">Fulfilment Milestones:</span>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="isPaidSwitch" value="1" <?= !empty($inquiry['is_paid']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="isPaidSwitch">Payment Received (Paid)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_dispatched" id="isDispatchedSwitch" value="1" <?= !empty($inquiry['is_dispatched']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="isDispatchedSwitch">Dispatched (Lorry Transport)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_delivered" id="isDeliveredSwitch" value="1" <?= !empty($inquiry['is_delivered']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="isDeliveredSwitch">Parcel Delivered to Customer</label>
                        </div>
                    </div>

                    <hr class="my-3">

                    <h6 class="fw-bold mb-3 text-dark">
                        <i class="fa-solid fa-truck me-2 text-info"></i>Customer Shipping & Contact
                    </h6>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Customer / Receiver Name <span class="text-danger">*</span></label>
                        <input type="text" name="shipping_name" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['shipping_name'] ?: $inquiry['user_name'] ?: '') ?>" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['contact_number'] ?: $inquiry['user_phone'] ?: '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Alternate Phone</label>
                            <input type="text" name="alternate_contact_number" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['alternate_contact_number'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['whatsapp_number'] ?? ($inquiry['contact_number'] ?: $inquiry['user_phone'] ?: '')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="customer_email" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['customer_email'] ?: $inquiry['user_email'] ?: '') ?>">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                        <input type="text" name="shipping_address_1" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['shipping_address_1'] ?? '') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Address Line 2 (Optional)</label>
                        <input type="text" name="shipping_address_2" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['shipping_address_2'] ?? '') ?>">
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">City / Town <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['city'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Pincode <span class="text-danger">*</span></label>
                            <input type="text" name="pincode" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['pincode'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">District</label>
                            <input type="text" name="district" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['district'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text" name="state" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['state'] ?: 'Tamil Nadu') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nearest Landmark</label>
                        <input type="text" name="landmark" class="form-control form-control-sm" value="<?= htmlspecialchars($inquiry['landmark'] ?? '') ?>" placeholder="e.g. Near Bus Stand">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Customer & Order Info
                    </button>
                </form>
            </div>

            <!-- Delete Enquiry Option -->
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-top border-danger border-2">
                <span class="text-danger small fw-bold text-uppercase d-block mb-1">Danger Zone</span>
                <p class="small text-muted mb-2">Permanently remove this customer inquiry and all ordered product items.</p>
                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this entire enquiry?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_inquiry">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="fa-solid fa-trash me-1"></i> Delete This Enquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addProductSelect = document.getElementById('addProductSelect');
    const addPriceInput = document.getElementById('addPriceInput');
    const productPrices = <?= json_encode($productPrices) ?>;

    if (addProductSelect && addPriceInput) {
        addProductSelect.addEventListener('change', function() {
            const prodId = this.value;
            if (productPrices[prodId] !== undefined) {
                addPriceInput.value = parseFloat(productPrices[prodId]).toFixed(2);
            } else {
                addPriceInput.value = '';
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
