<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, name, discount_percent FROM categories');
foreach ($stmt->fetchAll() as $row) {
    echo $row['id'] . " | " . $row['name'] . " | " . $row['discount_percent'] . "\n";
}
