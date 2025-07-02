<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/is_admin.php';
$view = __DIR__ . '/views/returned_items_page.php';


$page_title = "Returned Items Management";

// Fetch users for dropdown
$users_stmt = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
$users_list = $users_stmt ? $users_stmt->fetch_all(MYSQLI_ASSOC) : [];

// Fetch items for dropdown
$items_stmt = $conn->query("SELECT id, name, sku, unit, quantity, unit_price, price_taxed, price_nontaxed, description FROM items ORDER BY name ASC");
$items_list = $items_stmt ? $items_stmt->fetch_all(MYSQLI_ASSOC) : [];

// Fetch returned items
$returned_items_data = [];
$query = "SELECT ri.*, u_returned.username AS returned_by_username, u_received.username AS received_by_username
          FROM returned_items ri
          LEFT JOIN users u_returned ON ri.returned_by = u_returned.id
          LEFT JOIN users u_received ON ri.received_by = u_received.id
          ORDER BY ri.return_date DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $returned_items_data[] = $row;
    }
}

// For compatibility with the layout
$view = __DIR__ . '/views/returned_items_page.php';

// This is your main layout wrapper!
include __DIR__ . '/templates/layout.php';
?>
