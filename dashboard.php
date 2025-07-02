<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Get inventory statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(quantity) as total_quantity,
        SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_items,
        SUM(price_nontaxed * quantity) as total_nontaxed_value,
        SUM(price_taxed * quantity) as total_taxed_value
    FROM items
")->fetch_assoc();

// Get pending deliveries
$pending_deliveries = $conn->query("
    SELECT COUNT(*) as count 
    FROM delivery_receipts 
    WHERE outstanding > 0
")->fetch_assoc()['count'];

// Get pending material requests
$pending_requests = $conn->query("
    SELECT COUNT(*) as count 
    FROM materials_requests 
    WHERE status = 'pending'
")->fetch_assoc()['count'];

// Get recent activity (last 5 actions)
$recent_activity = $conn->query("
    SELECT 
        m.item_id,
        i.name as item_name,
        m.movement_type,
        m.quantity,
        m.reference_type,
        m.reference_id,
        m.created_at,
        u.username as user
    FROM inventory_movements m
    JOIN items i ON m.item_id = i.id
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$data = [
    'page_title' => 'Dashboard'
];

$view = 'views/dashboard.php';
include 'templates/layout.php';
?>