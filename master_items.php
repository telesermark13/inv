<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Set variables for your layout template
$page_title = "Master Items";
$view = __DIR__ . '/views/master_items_view.php';

// Fetch all master items
$sql = "SELECT * FROM master_items ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// This array will be extracted and available as $items in the view
$form_data = ['items' => $items];

// This will render the sidebar, header, etc., and load your $view in the content area
include __DIR__ . '/templates/layout.php';
