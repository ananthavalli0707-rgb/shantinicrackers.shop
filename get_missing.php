<?php
require __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT name FROM products WHERE tamil_name IS NULL OR tamil_name = ''");
$missing = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Products Missing Tamil Translation:</h3>";
if (!$missing) {
    echo "<p>NONE! All products are translated.</p>";
} else {
    echo "<pre>";
    foreach ($missing as $m) {
        echo htmlspecialchars($m) . "\n";
    }
    echo "</pre>";
}
