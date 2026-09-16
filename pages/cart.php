<?php
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/smtp.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$adminWhatsAppNumber = '917708971956';
$submitSuccess = $_SESSION['inquiry_success'] ?? '';
unset($_SESSION['inquiry_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    if ($productId > 0) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? url('cart');
    if (($pos = strpos($referer, '#')) !== false) {
        $referer = substr($referer, 0, $pos);
    }
    header("Location: $referer#product-$productId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    if ($productId > 0 && $quantity > 0) {
        $_SESSION['cart'][$productId] = $quantity;
    } else {
        unset($_SESSION['cart'][$productId]);
    }
    redirect('cart');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remove') {
    verify_csrf();
    unset($_SESSION['cart'][(int)($_POST['product_id'] ?? 0)]);
    redirect('cart');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'clear') {
    verify_csrf();
    $_SESSION['cart'] = [];
    redirect('cart');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    verify_csrf();
    $shippingName = trim($_POST['shipping_name'] ?? $_SESSION['user_name'] ?? '');
    $address1 = trim($_POST['shipping_address_1'] ?? '');
    $state = trim($_POST['shipping_state'] ?? '');
    $city = trim($_POST['shipping_city'] ?? '');
    $pincode = trim($_POST['shipping_pincode'] ?? '');
    $whatsappPhone = preg_replace('/\D+/', '', $_POST['whatsapp_number'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');

    if ($shippingName === '' || $address1 === '' || $city === '' || $pincode === '' || $state === '' || $whatsappPhone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $submitError = 'Please complete all shipping details and enter a valid email address.';
    } else {
        $dbItems = [];
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $stmt = $pdo->prepare('SELECT id, name, actual_price, discount_price FROM products WHERE id = :id');
            $stmt->execute(['id' => (int)$productId]);
            $product = $stmt->fetch();
            if ($product && (int)$quantity > 0) {
                $product['quantity'] = (int)$quantity;
                $product['subtotal'] = (float)$product['discount_price'] * (int)$quantity;
                $dbItems[] = $product;
            }
        }

        $actualTotal = 0;
        $discountedTotal = 0;
        foreach ($dbItems as $item) {
            $actualTotal += (float)$item['actual_price'] * $item['quantity'];
            $discountedTotal += (float)$item['discount_price'] * $item['quantity'];
        }
        $discountTotal = $actualTotal - $discountedTotal;
        $packagingTotal = round($discountedTotal * 0.03, 2);
        $amount = round($discountedTotal + $packagingTotal, 2);

        $minAmount = ($state === 'Tamil Nadu') ? 3000 : 5000;
        if ($amount < $minAmount) {
            $submitError = "Minimum estimate amount for {$state} is ₹{$minAmount}. Please add more items.";
        } elseif (!$dbItems) {
            $submitError = 'Your estimate sheet is empty.';
        } else {
            try {
                $pdo->beginTransaction();
                $userId = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                $stmt = $pdo->prepare('INSERT INTO inquiries (user_id, total_amount) VALUES (:user_id, :total_amount)');
                $stmt->execute(['user_id' => $userId, 'total_amount' => $amount]);
                $inquiryId = $pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO shipping_details (inquiry_id, shipping_name, shipping_address_1, shipping_address_2, city, pincode, district, state, landmark, contact_number, alternate_contact_number, whatsapp_number, customer_email) VALUES (:inquiry_id, :shipping_name, :address1, :address2, :city, :pincode, :district, :state, :landmark, :phone, :alternate_phone, :whatsapp_phone, :email)');
                $stmt->execute([
                    'inquiry_id' => $inquiryId, 'shipping_name' => $shippingName, 'address1' => $address1,
                    'address2' => '', 'city' => $city, 'pincode' => $pincode, 'district' => '',
                    'state' => $state, 'landmark' => '', 'phone' => $whatsappPhone,
                    'alternate_phone' => '', 'whatsapp_phone' => $whatsappPhone, 'email' => $email,
                ]);
                $stmt = $pdo->prepare('INSERT INTO inquiry_items (inquiry_id, product_id, quantity, price_at_booking) VALUES (:inquiry_id, :product_id, :quantity, :price)');
                foreach ($dbItems as $item) {
                    $stmt->execute(['inquiry_id' => $inquiryId, 'product_id' => $item['id'], 'quantity' => $item['quantity'], 'price' => $item['discount_price']]);
                }
                $pdo->commit();

                $message = "Shantini Crackers Estimate Request #{$inquiryId}\n\nName: {$shippingName}\nAddress: {$address1}";
                $message .= "\nCity: {$city}\nState: {$state}\nPincode: {$pincode}";
                $message .= "\nCustomer WhatsApp: {$whatsappPhone}";
                $message .= "\nEmail: {$email}\n\nProducts:\n";
                foreach ($dbItems as $item) {
                    $message .= "- {$item['name']} x {$item['quantity']} = Rs. " . number_format($item['subtotal'], 2) . "\n";
                }
                $message .= "\nActual total: Rs. " . number_format($actualTotal, 2) . "\nDiscount: Rs. " . number_format($discountTotal, 2) . "\nPackaging: Rs. " . number_format($packagingTotal, 2) . "\nEstimate amount: Rs. " . number_format($amount, 2);

                $mailResult = send_smtp_email($email, "Shantini Crackers Estimate Request #{$inquiryId}", $message, false);
                $emailSent = $mailResult['success'];

                $_SESSION['cart'] = [];
                $_SESSION['inquiry_success'] = 'Request #' . (int)$inquiryId . ' was saved. ' . ($emailSent ? 'The confirmation email was accepted for delivery to ' . $email . '.' : 'The request was saved, but the confirmation email could not be sent because the server email service is not configured.') . ' WhatsApp will open on your device with this message addressed to the admin. Press Send to notify the admin.';
                header('Location: https://wa.me/' . $adminWhatsAppNumber . '?text=' . rawurlencode($message));
                exit;
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $submitError = 'We could not save your inquiry. Please try again.';
            }
        }
    }
}

$cartItems = [];
$actualTotal = 0;
$discountedTotal = 0;
foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $pdo->prepare('SELECT products.*, image_url AS image FROM products WHERE id = :id');
    $stmt->execute(['id' => (int)$productId]);
    $product = $stmt->fetch();
    if (!$product || (int)$quantity <= 0) continue;
    $product['quantity'] = (int)$quantity;
    $product['subtotal'] = (float)$product['discount_price'] * (int)$quantity;
    $actualTotal += (float)$product['actual_price'] * (int)$quantity;
    $discountedTotal += (float)$product['discount_price'] * (int)$quantity;
    $cartItems[] = $product;
}
$discountTotal = $actualTotal - $discountedTotal;
$packagingTotal = round($discountedTotal * 0.03, 2);
$amount = round($discountedTotal + $packagingTotal, 2);
?>

<div class="section-padding bg-surface">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h1-display mb-0">My Estimate</h1>
                <p class="text-muted mt-2 mb-0">Review your selected items and request a quote.</p>
            </div>
            <?php if (!empty($cartItems)): ?>
            <div class="col-auto">
                <span class="badge bg-primary fs-5 px-3 py-2 rounded-pill"><?= count($cartItems) ?> Items</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($submitSuccess !== ''): ?>
            <div class="card shadow-sm mb-4 mx-auto border-success" style="max-width: 600px;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 fw-bold">🎆 Shantini Crackers - Order Confirmed</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 fs-5"><?= htmlspecialchars($submitSuccess) ?></p>
                    <?php if (!empty($_SESSION['is_admin'])): ?><p class="mt-3 mb-0"><a href="<?= url('admin-inquiries') ?>" class="btn btn-sm btn-outline-success">Open Customer Inquiries</a></p><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($submitError)): ?>
            <div class="alert alert-warning shadow-sm mb-4"><?= htmlspecialchars($submitError) ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="text-center py-5 bg-white rounded shadow-sm border border-color">
                <i class="fa-solid fa-cart-shopping mb-4 text-muted" style="font-size: 4rem;"></i>
                <h3 class="h3-card mb-3">Your estimate is empty.</h3>
                <p class="text-muted mb-4">Browse our products and add items to build your estimate.</p>
                <a href="<?= url('products') ?>" class="btn-primary px-5">Explore Products</a>
            </div>
        <?php else: ?>
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="bg-white rounded shadow-sm border border-color overflow-hidden">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light text-uppercase text-muted" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="ps-4 py-3">Product</th>
                                        <th class="py-3 text-center">Price</th>
                                        <th class="py-3 text-center">Qty</th>
                                        <th class="py-3 text-end">Total</th>
                                        <th class="pe-4 py-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $product): ?>
                                        <tr>
                                            <td class="ps-4 py-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="<?= asset('uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="rounded" style="width: 60px; height: 60px; object-fit: cover; background: var(--bg-surface);">
                                                    <?php else: ?>
                                                        <div class="rounded d-flex align-items-center justify-content-center bg-light text-secondary" style="width: 60px; height: 60px; font-size: 1.5rem;">🧨</div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold" style="font-family: var(--font-heading);"><?= htmlspecialchars($product['name']) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 text-center fw-bold">₹<?= htmlspecialchars($product['discount_price']) ?></td>
                                            <td class="py-4">
                                                <form method="POST" class="d-flex justify-content-center">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                                    <div class="quantity-control mb-0" style="width: 110px;">
                                                        <button type="button" class="quantity-btn px-2" onclick="this.nextElementSibling.stepDown(); this.form.submit();">-</button>
                                                        <input type="number" name="quantity" value="<?= (int)$product['quantity'] ?>" min="0" class="quantity-input px-1" onchange="this.form.submit();">
                                                        <button type="button" class="quantity-btn px-2" onclick="this.previousElementSibling.stepUp(); this.form.submit();">+</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="py-4 text-end fw-bold text-primary">₹<?= number_format($product['subtotal'], 2) ?></td>
                                            <td class="pe-4 py-4 text-center">
                                                <form method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                                    <button class="btn btn-sm text-danger" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-light p-3 d-flex justify-content-between align-items-center border-top">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to clear your entire estimate?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn text-danger fw-bold"><i class="fa-solid fa-trash-can me-2"></i>Clear Estimate</button>
                            </form>
                            <a href="<?= url('products') ?>" class="btn-primary text-decoration-none">Add More Products</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="bg-white rounded shadow-sm border border-color p-4 sticky-top" style="top: 100px;">
                        <h3 class="h3-card mb-4 pb-3 border-bottom">Estimate Summary</h3>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Actual Total</span>
                            <span class="fw-bold">₹<?= number_format($actualTotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-success fw-bold">
                            <span>Discount Saving</span>
                            <span>- ₹<?= number_format($discountTotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Packaging (3%)</span>
                            <span class="fw-bold">₹<?= number_format($packagingTotal, 2) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                            <span class="h3-card mb-0">Estimated Amount</span>
                            <span class="fs-3 fw-bold text-primary">₹<?= number_format($amount, 2) ?></span>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top text-center">
                            <p class="text-muted small mb-4">This is an estimated amount. Final billing amount will be confirmed by admin.</p>
                        </div>
                        
                        <form method="POST" class="mt-4" id="checkoutForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="submit">
                            <h4 class="h3-card mb-3 fs-5">Shipping Details</h4>
                            <input class="form-control mb-3 py-2" name="shipping_name" placeholder="Full Name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
                            <input class="form-control mb-3 py-2" type="email" name="customer_email" placeholder="Verified Email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
                            <input class="form-control mb-3 py-2" type="tel" name="whatsapp_number" placeholder="WhatsApp number" required>
                            <input class="form-control mb-3 py-2" name="shipping_address_1" placeholder="Detailed Address" required>
                            <input class="form-control mb-3 py-2" name="shipping_pincode" placeholder="Pincode" required>
                            
                            <select class="form-select mb-3 py-2" name="shipping_state" id="stateSelect" required>
                                <option value="">Select State</option>
                                <option value="Tamil Nadu">Tamil Nadu</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                <option value="Telangana">Telangana</option>
                                <option value="Haryana">Haryana</option>
                                <option value="Rajasthan">Rajasthan</option>
                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                <option value="Kerala">Kerala</option>
                            </select>
                            
                            <select class="form-select mb-4 py-2" name="shipping_city" id="citySelect" required disabled>
                                <option value="">Select City</option>
                            </select>
                            
                            <div id="limitWarning" class="alert alert-warning d-none mb-3 small fw-bold shadow-sm"></div>

                            <button type="submit" id="submitBtn" class="w-100 py-3 mt-2 shadow-sm" style="background: #25D366; color: white; border: none; border-radius: var(--radius-sm); font-weight: bold; font-size: 1.1rem; transition: transform 0.2s;">
                                <i class="fa-brands fa-whatsapp me-2 fs-4 align-middle"></i> Send via WhatsApp
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const cityData = {
        "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli", "Salem", "Tirunelveli", "Erode", "Vellore", "Sivakasi", "Other"],
        "Karnataka": ["Bengaluru", "Mysuru", "Hubballi", "Mangaluru", "Belagavi", "Other"],
        "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool", "Other"],
        "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Khammam", "Karimnagar", "Other"],
        "Haryana": ["Faridabad", "Gurugram", "Panipat", "Ambala", "Rohtak", "Other"],
        "Rajasthan": ["Jaipur", "Jodhpur", "Kota", "Bikaner", "Ajmer", "Other"],
        "Uttar Pradesh": ["Lucknow", "Kanpur", "Ghaziabad", "Agra", "Varanasi", "Other"],
        "Kerala": ["Thiruvananthapuram", "Kochi", "Kozhikode", "Thrissur", "Kollam", "Other"]
    };

    const cartTotal = <?= $amount ?>;
    const stateSelect = document.getElementById('stateSelect');
    const citySelect = document.getElementById('citySelect');
    const submitBtn = document.getElementById('submitBtn');
    const limitWarning = document.getElementById('limitWarning');

    stateSelect.addEventListener('change', function() {
        const state = this.value;
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (state && cityData[state]) {
            citySelect.disabled = false;
            cityData[state].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
            
            // Validate minimum amount
            const minAmount = (state === 'Tamil Nadu') ? 3000 : 5000;
            if (cartTotal < minAmount) {
                limitWarning.textContent = `Minimum cart limit for ${state} is ₹${minAmount}. Please add more items.`;
                limitWarning.classList.remove('d-none');
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.pointerEvents = 'none';
            } else {
                limitWarning.classList.add('d-none');
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.pointerEvents = 'auto';
            }
        } else {
            citySelect.disabled = true;
            limitWarning.classList.add('d-none');
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.pointerEvents = 'auto';
        }
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
