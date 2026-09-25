<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, name FROM products WHERE category_id = (SELECT id FROM categories WHERE name LIKE '%Fancy Novelties%' LIMIT 1)");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as $p) {
    echo $p['id'] . " - " . $p['name'] . "\n";
}
