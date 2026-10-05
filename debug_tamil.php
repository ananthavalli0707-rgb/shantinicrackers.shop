<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "--- Categories ---\n";
$stmt = $pdo->query('SELECT id, name, tamil_name FROM categories');
while ($row = $stmt->fetch()) {
    echo "{$row['id']} | {$row['name']} | " . ($row['tamil_name'] ?? 'NULL') . "\n";
}

echo "\n--- Products ---\n";
$stmt = $pdo->query('SELECT id, name, tamil_name FROM products');
while ($row = $stmt->fetch()) {
    echo "{$row['id']} | {$row['name']} | " . ($row['tamil_name'] ?? 'NULL') . "\n";
}
