<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    header('Location: index.php');
    exit;
}

$tools = [];
$sql = "SELECT t.*, u.username AS assigned_person_name
        FROM tools t
        LEFT JOIN users u ON t.assigned_person = u.id
        ORDER BY t.name ASC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tools[] = $row;
    }
}

$view = __DIR__ . '/views/tools_content.php'; 
$page_title = "Tools | Inventory System";
$base_url = "";

include __DIR__ . '/templates/layout.php';
