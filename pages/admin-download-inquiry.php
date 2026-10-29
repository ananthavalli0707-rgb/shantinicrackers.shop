<?php
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/pdf.php';

if (empty($_SESSION['is_admin'])) {
    redirect('admin-login');
}

// The shared header is buffered; discard it because this route returns a PDF.
ob_end_clean();

$inquiryId = (int)($_GET['id'] ?? 0);
$pdfData = generate_inquiry_pdf($inquiryId, $pdo);

if (!$pdfData) {
    http_response_code(404);
    exit('Inquiry not found.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdfData['filename'] . '"');
header('Content-Length: ' . strlen($pdfData['content']));
header('X-Content-Type-Options: nosniff');
echo $pdfData['content'];
exit;

