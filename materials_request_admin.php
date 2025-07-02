<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = 'Materials Request Approval';

// --- Fetch all requesters for dropdown ---
$requesters = [];
$res = $conn->query("SELECT DISTINCT u.id, u.username FROM users u JOIN materials_requests mr ON mr.user_id = u.id");
while ($row = $res->fetch_assoc()) {
    $requesters[] = $row;
}

// --- Filters ---
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$requester_filter = $_GET['requester'] ?? 'all';

// --- Build the WHERE clause and params ---
$where = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($date_filter) {
    $where[] = "DATE(r.request_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}
if ($requester_filter !== 'all') {
    $where[] = "u.username = ?";
    $params[] = $requester_filter;
    $types .= "s";
}

// --- SQL Query ---
$query = "SELECT r.*, u.username as requester_username, a.username as processed_by_username
          FROM materials_requests r
          JOIN users u ON r.user_id = u.id
          LEFT JOIN users a ON r.processed_by = a.id";
if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY r.id DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result();

// CSRF token for security
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// --- Main Layout: ---
// This will render your sidebar, nav, etc., and include this page content in the correct section
$view = 'views/materials_request_admin.php';
include 'templates/layout.php';
