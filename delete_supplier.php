<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: suppliers.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header("Location: suppliers.php");
    exit;
}
$id = (int)($_POST['supplier_id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Supplier deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete supplier.";
    }
    $stmt->close();
}
header("Location: suppliers.php");
exit;
?>
