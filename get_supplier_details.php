<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid supplier ID.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($supplier = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $supplier]);
} else {
    echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
}
$stmt->close();
?>
