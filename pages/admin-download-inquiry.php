<?php
require __DIR__ . '/../includes/header.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

// The shared header is buffered; discard it because this route returns a PDF.
ob_end_clean();

function pdf_text(string $text): string {
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';
    return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $text);
}

function create_simple_pdf(array $lines): string {
    $wrappedLines = [];
    foreach ($lines as $line) {
        $parts = wordwrap((string)$line, 88, "\n", true);
        foreach (explode("\n", $parts ?: ' ') as $part) {
            $wrappedLines[] = $part;
        }
    }
    $linePages = array_chunk($wrappedLines, 48);
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];
    $pageIds = [];
    $nextId = 4;
    foreach ($linePages as $pageLines) {
        $pageId = $nextId++;
        $contentId = $nextId++;
        $pageIds[] = $pageId;
        $content = "BT\n/F1 10 Tf\n50 790 Td\n";
        foreach ($pageLines as $line) {
            $content .= '(' . pdf_text($line) . ") Tj\n0 -15 Td\n";
        }
        $content .= "ET";
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
        $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($id = 1; $id <= count($objects); $id++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n";
    }
    return $pdf . 'trailer' . "\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";
}

$inquiryId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT inquiries.*, users.name AS user_name, users.phone, shipping_details.shipping_name, shipping_details.shipping_address_1, shipping_details.shipping_address_2, shipping_details.city, shipping_details.pincode, shipping_details.district, shipping_details.state, shipping_details.landmark, shipping_details.contact_number, shipping_details.alternate_contact_number, shipping_details.whatsapp_number, shipping_details.customer_email FROM inquiries LEFT JOIN users ON users.id = inquiries.user_id LEFT JOIN shipping_details ON shipping_details.inquiry_id = inquiries.id WHERE inquiries.id = :id');
$stmt->execute(['id' => $inquiryId]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    http_response_code(404);
    exit('Inquiry not found.');
}

$stmt = $pdo->prepare('SELECT inquiry_items.quantity, inquiry_items.price_at_booking, products.name AS product_name FROM inquiry_items LEFT JOIN products ON products.id = inquiry_items.product_id WHERE inquiry_items.inquiry_id = :id ORDER BY inquiry_items.id');
$stmt->execute(['id' => $inquiryId]);
$items = $stmt->fetchAll();

$customerName = $inquiry['shipping_name'] ?: ($inquiry['user_name'] ?: 'Customer');
$lines = [
    'SHANTINI CRACKERS',
    'ESTIMATE REQUEST #' . $inquiryId,
    'Created: ' . $inquiry['created_at'],
    '',
    'Customer: ' . $customerName,
    'Phone: ' . ($inquiry['contact_number'] ?: ($inquiry['phone'] ?: '-')),
    'Email: ' . ($inquiry['customer_email'] ?: '-'),
    'Address: ' . trim(implode(', ', array_filter([$inquiry['shipping_address_1'], $inquiry['shipping_address_2'], $inquiry['city'], $inquiry['district'], $inquiry['state'], $inquiry['pincode']]))),
    'Status: ' . ($inquiry['status'] ?: 'Pending Review'),
    '',
    'ITEMS',
];
foreach ($items as $item) {
    $name = $item['product_name'] ?: 'Deleted product';
    $subtotal = (float)$item['price_at_booking'] * (int)$item['quantity'];
    $lines[] = $name . ' | Qty: ' . (int)$item['quantity'] . ' | Rs. ' . number_format($subtotal, 2);
}
$lines[] = '';
$lines[] = 'ESTIMATE TOTAL: Rs. ' . number_format((float)$inquiry['total_amount'], 2);

$filename = 'shantini-inquiry-' . $inquiryId . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf = create_simple_pdf($lines)));
header('X-Content-Type-Options: nosniff');
echo $pdf;
exit;
