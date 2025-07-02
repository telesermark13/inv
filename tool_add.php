<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    exit('Access denied');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_person = $_POST['assigned_person'] ?: null;
    $status = $_POST['status'] ?? 'good';

    if (!$name || !$size) {
        exit('Name and size are required.');
    }

    $stmt = $conn->prepare("INSERT INTO tools (name, quantity, size, description, assigned_person, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissis", $name, $quantity, $size, $description, $assigned_person, $status);
    if ($stmt->execute()) {
        exit('success');
    } else {
        exit('Database error: ' . $conn->error);
    }
}
exit('Invalid request');
