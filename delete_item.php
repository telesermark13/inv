<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: items.php");
    exit;
}

// Check if ID is provided and valid
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Invalid item ID";
    header("Location: items.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "CSRF token validation failed";
    header("Location: items.php");
    exit;
}

$id = (int)$_POST['id'];

// Begin transaction
$conn->begin_transaction();

try {
    // Check if item exists with prepared statement
    $stmt = $conn->prepare("SELECT id FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Item not found!");
    }

    // Delete item with prepared statement
    $delete_stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Error deleting item: " . $conn->error);
    }

    $conn->commit();
    $_SESSION['success'] = "Item deleted successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: items.php");
exit;
?>