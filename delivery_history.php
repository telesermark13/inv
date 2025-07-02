<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Handle filters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$requester_filter = $_GET['requester'] ?? 'all';

// Base query
$query = "SELECT dr.*, u.username as prepared_by_name 
          FROM delivery_receipts dr
          LEFT JOIN users u ON dr.prepared_by = u.id
          WHERE 1=1";

// Apply filters
if ($status_filter === 'outstanding') {
    $query .= " AND dr.outstanding > 0";
} elseif ($status_filter === 'completed') {
    $query .= " AND dr.outstanding = 0";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(dr.date) = '" . mysqli_real_escape_string($conn, $date_filter) . "'";
}

if ($requester_filter !== 'all') {
    $query .= " AND u.username = '" . mysqli_real_escape_string($conn, $requester_filter) . "'";
}

$query .= " ORDER BY dr.date DESC, dr.delivery_number DESC";

$deliveries = mysqli_query($conn, $query);

// Get list of requesters for filter
$requesters = mysqli_query($conn, "SELECT DISTINCT u.username 
                                  FROM delivery_receipts dr
                                  LEFT JOIN users u ON dr.prepared_by = u.id
                                  ORDER BY u.username");

// Prepare data for the view
$data = [
    'page_title' => 'Delivery History'
];

$view = 'views/delivery_history_view.php';
include 'templates/layout.php';