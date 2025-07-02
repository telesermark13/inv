<?php
// inventory-system/suppliers.php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/is_admin.php';

$page_title = "Suppliers Management";

// Fetch all suppliers
$suppliers_data = [];
$result = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers_data[] = $row;
    }
    $result->close();
}

$view = VIEWS_PATH . 'suppliers.php';
include __DIR__ . '/templates/layout.php';
?>
