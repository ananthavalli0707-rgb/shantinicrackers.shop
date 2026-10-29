<?php
// includes/pdf.php

if (!function_exists('pdf_text')) {
    function pdf_text(string $text): string {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';
        return str_replace(["\\", '(', ')', "\r", "\n"], ["\\\\", '\\(', '\\)', '', ' '], $text);
    }
}

if (!function_exists('create_simple_pdf')) {
    function create_simple_pdf(array $lines): string {
        $wrappedLines = [];
        foreach ($lines as $line) {
            $parts = wordwrap((string)$line, 86, "\n", true);
            foreach (explode("\n", $parts ?: ' ') as $part) {
                $wrappedLines[] = $part;
            }
        }
        $linePages = array_chunk($wrappedLines, 48);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
        ];
        $pageIds = [];
        $nextId = 4;
        foreach ($linePages as $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $content = "BT\n/F1 10 Tf\n45 790 Td\n";
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
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n";
        }
        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";
    }
}

/**
 * Generates an inquiry estimate PDF from the database.
 * Returns an array with ['filename' => string, 'content' => string, 'inquiry' => array, 'items' => array]
 */
function generate_inquiry_pdf(int $inquiryId, PDO $pdo): ?array {
    $stmt = $pdo->prepare('SELECT inquiries.*, users.name AS user_name, users.phone, 
        shipping_details.shipping_name, shipping_details.shipping_address_1, shipping_details.shipping_address_2, 
        shipping_details.city, shipping_details.pincode, shipping_details.district, shipping_details.state, 
        shipping_details.landmark, shipping_details.contact_number, shipping_details.alternate_contact_number, 
        shipping_details.whatsapp_number, shipping_details.customer_email 
        FROM inquiries 
        LEFT JOIN users ON users.id = inquiries.user_id 
        LEFT JOIN shipping_details ON shipping_details.inquiry_id = inquiries.id 
        WHERE inquiries.id = :id');
    $stmt->execute(['id' => $inquiryId]);
    $inquiry = $stmt->fetch();

    if (!$inquiry) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT inquiry_items.quantity, inquiry_items.price_at_booking, products.name AS product_name 
        FROM inquiry_items 
        LEFT JOIN products ON products.id = inquiry_items.product_id 
        WHERE inquiry_items.inquiry_id = :id 
        ORDER BY inquiry_items.id');
    $stmt->execute(['id' => $inquiryId]);
    $items = $stmt->fetchAll();

    $customerName = $inquiry['shipping_name'] ?: ($inquiry['user_name'] ?: 'Customer');
    $phone = $inquiry['whatsapp_number'] ?: ($inquiry['contact_number'] ?: ($inquiry['phone'] ?: '-'));
    $email = $inquiry['customer_email'] ?: '-';
    $addressParts = array_filter([
        $inquiry['shipping_address_1'],
        $inquiry['shipping_address_2'],
        $inquiry['city'],
        $inquiry['district'],
        $inquiry['state'],
        $inquiry['pincode'] ? "- " . $inquiry['pincode'] : ''
    ]);
    $fullAddress = implode(', ', $addressParts);

    $divider = str_repeat('=', 78);
    $subDivider = str_repeat('-', 78);

    $lines = [
        $divider,
        "                     SHANTINI CRACKERS - SIVAKASI                      ",
        "                      ESTIMATE / ORDER INQUIRY                         ",
        $divider,
        sprintf("Estimate ID  : #%s", $inquiryId),
        sprintf("Date & Time  : %s", $inquiry['created_at'] ?? date('Y-m-d H:i:s')),
        sprintf("Status       : %s", $inquiry['status'] ?: 'Pending Review'),
        $subDivider,
        "CUSTOMER & DELIVERY DETAILS:",
        sprintf("Name         : %s", $customerName),
        sprintf("WhatsApp/Ph  : %s", $phone),
        sprintf("Email        : %s", $email),
        sprintf("Address      : %s", $fullAddress),
        $subDivider,
        sprintf("%-4s %-42s %-6s %-10s %-12s", "#", "Product Name", "Qty", "Price", "Total (Rs)"),
        $subDivider,
    ];

    $count = 0;
    $grandTotal = 0.0;
    foreach ($items as $idx => $item) {
        $count++;
        $name = $item['product_name'] ?: 'Product';
        $qty = (int)$item['quantity'];
        $price = (float)$item['price_at_booking'];
        $subtotal = $qty * $price;
        $grandTotal += $subtotal;

        // Truncate name safely if long
        $displayName = mb_strimwidth($name, 0, 41, '...');
        $lines[] = sprintf("%-4d %-42s %-6d %-10.2f %-12.2f", $count, $displayName, $qty, $price, $subtotal);
    }

    $lines[] = $subDivider;
    $lines[] = sprintf("%-54s Rs. %12.2f", "ESTIMATE TOTAL:", (float)$inquiry['total_amount']);
    $lines[] = $divider;
    $lines[] = "";
    $lines[] = "Thank you for placing your inquiry with Shantini Crackers!";
    $lines[] = "Contact: +91 9786361678 / +91 7708971956 | Email: shantinicrackerssivakasi@gmail.com";

    $filename = "shantini-estimate-{$inquiryId}.pdf";
    $pdfContent = create_simple_pdf($lines);

    return [
        'filename' => $filename,
        'content'  => $pdfContent,
        'inquiry'  => $inquiry,
        'items'    => $items
    ];
}
