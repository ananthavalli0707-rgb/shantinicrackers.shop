<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare('UPDATE inquiries SET status = :status WHERE id = :id');
    $stmt->execute(['status' => trim($_POST['status'] ?? 'Pending Review'), 'id' => (int)($_POST['inquiry_id'] ?? 0)]);
    redirect('admin-inquiries');
}

$inquiries = $pdo->query('SELECT inquiries.*, users.name AS user_name, users.phone, shipping_details.shipping_name, shipping_details.shipping_address_1, shipping_details.shipping_address_2, shipping_details.city, shipping_details.pincode, shipping_details.district, shipping_details.state, shipping_details.landmark, shipping_details.contact_number, shipping_details.alternate_contact_number, shipping_details.whatsapp_number, shipping_details.customer_email, inquiry_items.quantity, inquiry_items.price_at_booking, products.name AS product_name FROM inquiries LEFT JOIN users ON users.id = inquiries.user_id LEFT JOIN shipping_details ON shipping_details.inquiry_id = inquiries.id LEFT JOIN inquiry_items ON inquiry_items.inquiry_id = inquiries.id LEFT JOIN products ON products.id = inquiry_items.product_id ORDER BY inquiries.created_at DESC')->fetchAll();
$inquiryGroups = [];
foreach ($inquiries as $row) {
    $inquiryId = (int)$row['id'];
    if (!isset($inquiryGroups[$inquiryId])) {
        $inquiryGroups[$inquiryId] = $row;
        $inquiryGroups[$inquiryId]['items'] = [];
    }
    if ($row['product_name'] !== null) {
        $inquiryGroups[$inquiryId]['items'][] = $row;
    }
}
?>

<section>
    <h2 class="fw-bold mb-4">Customer Inquiries</h2>
    <div class="table-responsive">
        <table class="table table-bordered align-middle admin-table">
            <thead>
                <tr><th>Customer</th><th>Shopping details</th><th>Delivery details</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($inquiryGroups as $inquiry): ?><tr>
                    <td><strong>#<?= (int)$inquiry['id'] ?> - <?= htmlspecialchars($inquiry['shipping_name'] ?: $inquiry['user_name'] ?: 'Customer') ?></strong><br><?= htmlspecialchars($inquiry['contact_number'] ?: $inquiry['phone'] ?: '') ?><br><small><?= htmlspecialchars($inquiry['created_at']) ?></small><br><a class="btn btn-sm btn-outline-danger mt-2" href="<?= url('admin-download-inquiry', ['id' => (int)$inquiry['id']]) ?>">Download PDF</a></td>
                    <td><?php foreach ($inquiry['items'] as $item): ?><?= htmlspecialchars($item['product_name']) ?> x <?= (int)$item['quantity'] ?> (₹<?= number_format((float)$item['price_at_booking'] * (int)$item['quantity'], 2) ?>)<br><?php endforeach; ?><strong>Amount: ₹<?= number_format((float)$inquiry['total_amount'], 2) ?></strong></td>
                    <td><?= htmlspecialchars($inquiry['shipping_address_1'] ?? '') ?><?php if (!empty($inquiry['shipping_address_2'])): ?>, <?= htmlspecialchars($inquiry['shipping_address_2']) ?><?php endif; ?><br><?= htmlspecialchars($inquiry['city'] ?? '') ?>, <?= htmlspecialchars($inquiry['district'] ?? '') ?>, <?= htmlspecialchars($inquiry['state'] ?? '') ?> - <?= htmlspecialchars($inquiry['pincode'] ?? '') ?><?php if (!empty($inquiry['landmark'])): ?><br>Landmark: <?= htmlspecialchars($inquiry['landmark']) ?><?php endif; ?><?php if (!empty($inquiry['alternate_contact_number'])): ?><br>Alternate: <?= htmlspecialchars($inquiry['alternate_contact_number']) ?><?php endif; ?><?php if (!empty($inquiry['whatsapp_number'])): ?><br>WhatsApp: <?= htmlspecialchars($inquiry['whatsapp_number']) ?><?php endif; ?><?php if (!empty($inquiry['customer_email'])): ?><br><?= htmlspecialchars($inquiry['customer_email']) ?><?php endif; ?></td>
                    <td><form method="POST" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="inquiry_id" value="<?= (int)$inquiry['id'] ?>"><select name="status" class="form-select form-select-sm"><option <?= $inquiry['status'] === 'Pending Review' ? 'selected' : '' ?>>Pending Review</option><option <?= $inquiry['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option><option <?= $inquiry['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option></select><button class="btn btn-sm btn-outline-dark">Save</button></form></td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
