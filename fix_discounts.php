<?php
require 'config/db.php';

try {
    $pdo->exec('UPDATE products p JOIN categories c ON p.category_id = c.id SET p.discount_price = ROUND(p.actual_price * (1 - c.discount_percent / 100), 2)');
    echo "Products updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
