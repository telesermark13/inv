<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/is_admin.php'; // Old Stocks management is admin only

$page_title = "Old Stocks Management";

// Fetch all old stocks, joining with suppliers table if supplier_id is used
$old_stocks_data = [];
// Example query, adjust if your 'old_stocks' table has different columns or relations
$query = "SELECT os.*, s.name as supplier_name
          FROM old_stocks os
          LEFT JOIN suppliers s ON os.supplier_id = s.id
          ORDER BY os.name ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $old_stocks_data[] = $row;
    }
    $result->close();
}

// Fetch suppliers for "Add/Edit Old Stock" modal dropdown
$suppliers_list = [];
$suppliers_result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
if ($suppliers_result) {
    while ($supplier = $suppliers_result->fetch_assoc()) {
        $suppliers_list[] = $supplier;
    }
    $suppliers_result->close();
}


$view_data = [
    'old_stocks_data' => $old_stocks_data,
    'suppliers_list' => $suppliers_list,
];

$view = VIEWS_PATH . 'old_stocks.php'; // This view needs to be created
include __DIR__ . '/templates/layout.php';
?>