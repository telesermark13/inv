<?php
// Make sure this file is in the same directory as your main master_items.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

// --- Prepare and Execute SQL Query ---
$sql = "UPDATE master_items SET 
            name = ?,
            sku = ?,
            description = ?,
            category = ?,
            unit = ?,
            quantity = ?,
            unit_price = ?,
            price_taxed = ?,
            price_nontaxed = ?,
            min_stock_level = ?,
            price = ?,
            status = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}

// Bind the parameters
$stmt->bind_param(
    'sssssdddiidsi', // s = string, d = double, i = integer
    $_POST['name'],
    $_POST['sku'],
    $_POST['description'],
    $_POST['category'],
    $_POST['unit'],
    $_POST['quantity'],
    $_POST['unit_price'],
    $_POST['price_taxed'],
    $_POST['price_nontaxed'],
    $_POST['min_stock_level'],
    $_POST['price'],
    $_POST['status'],
    $_POST['id']
);

// --- Handle Success or Failure ---
if ($stmt->execute()) {
    http_response_code(200); // OK
    echo json_encode(['message' => 'Item updated successfully!']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to update item: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>