<?php
require 'config/db.php';

// Check if category exists
$stmt = $pdo->prepare("SELECT id FROM categories WHERE name LIKE '%Child%' OR name LIKE '%Kids%'");
$stmt->execute();
$category = $stmt->fetch();

if (!$category) {
    // Create it
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES ('Child Crackers')");
    $stmt->execute();
    $categoryId = $pdo->lastInsertId();
    echo "Created category Child Crackers with ID $categoryId\n";
} else {
    $categoryId = $category['id'];
    echo "Category Child Crackers already exists with ID $categoryId\n";
}

// Kids items from New arrivals
$kidsItems = [
    'Lollipop', 'Money Bank', 'Angry Bird', 'Selfie Stick', 'Magic Show', 
    'Chit Put', 'Helicopter', 'Butterfly Green', 'Bambaram', 'Colour Smoke'
];

$placeholders = implode(',', array_fill(0, count($kidsItems), '?'));
$stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE name IN ($placeholders) AND category_id = (SELECT id FROM categories WHERE name = 'New Arrivals' LIMIT 1)");
$params = array_merge([$categoryId], $kidsItems);
$stmt->execute($params);

echo "Moved " . $stmt->rowCount() . " items to Child Crackers.\n";
