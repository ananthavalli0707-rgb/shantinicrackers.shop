<?php
require 'config/db.php';

// Check if category exists
$stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'New Arrivals'");
$stmt->execute();
$category = $stmt->fetch();

if (!$category) {
    // Create it
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES ('New Arrivals')");
    $stmt->execute();
    $categoryId = $pdo->lastInsertId();
    echo "Created category New Arrivals with ID $categoryId\n";
} else {
    $categoryId = $category['id'];
    echo "Category New Arrivals already exists with ID $categoryId\n";
}

// Move all ground level items here
// Let's guess ground level items based on typical cracker names
$groundItems = [
    'Butterfly Green', 'Chit Put', 'Colour Shower', 'Helicopter', 'Old is Gold',
    'Colour Rain', 'Bambaram', 'Selfie Stick', 'Hi-Fi Pencil', 'Photo Flash',
    'Magic Show', 'Colour Smoke', 'Lollipop', 'Money Bank', 'Rotating Sparklers',
    'Angry Bird', '4 X 4 Wheel'
];

$placeholders = implode(',', array_fill(0, count($groundItems), '?'));
$stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE name IN ($placeholders) AND category_id = (SELECT id FROM categories WHERE name LIKE '%Fancy Novelties%' LIMIT 1)");
$params = array_merge([$categoryId], $groundItems);
$stmt->execute($params);

echo "Moved " . $stmt->rowCount() . " items to New Arrivals.\n";
