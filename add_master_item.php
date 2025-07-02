<?php
require_once __DIR__ . '/includes/db.php';


$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? ''); // nullable in DB
$description = trim($_POST['description'] ?? '');
$unit = trim($_POST['unit'] ?? ''); // nullable in DB
$quantity = (float)($_POST['quantity'] ?? 0);
$unit_price = isset($_POST['unit_price']) && $_POST['unit_price'] !== '' ? (float)$_POST['unit_price'] : null;
$min_stock_level = (int)($_POST['min_stock_level'] ?? 0);
$taxable = isset($_POST['taxable']) && $_POST['taxable'] === 'on';
$category = trim($_POST['category'] ?? ''); // nullable in DB

$price_nontaxed = $unit_price;
$price_taxed = $taxable ? $unit_price * 1.12 : $unit_price;

// Only name is strictly required per your table (id, name, quantity, min_stock_level are NOT NULL)
$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$description = trim($_POST['description'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$category = trim($_POST['category'] ?? '');

$sku = $sku !== '' ? $sku : null;
$description = $description !== '' ? $description : null;
$unit = $unit !== '' ? $unit : null;
$category = $category !== '' ? $category : null;

$stmt = $conn->prepare("
    INSERT INTO master_items
        (name, sku, description, unit, quantity, unit_price, min_stock_level, price_taxed, price_nontaxed, category)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "ssssidddds",
    $name,
    $sku,
    $description,
    $unit,
    $quantity,
    $unit_price,
    $min_stock_level,
    $price_taxed,
    $price_nontaxed,
    $category
);


if ($stmt->execute()) {
    echo "success";
} 
$stmt->close();
exit;
