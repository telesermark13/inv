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

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($name)) {
    $_SESSION['error'] = "Supplier name is required.";
    header("Location: suppliers.php");
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE suppliers SET 
        name = ?, contact_person = ?, email = ?, phone = ?, address = ?
        WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: suppliers.php");
        exit;
    }
    $stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO suppliers 
        (name, contact_person, email, phone, address)
        VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: suppliers.php");
        exit;
    }
    $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Supplier saved successfully.";
} else {
    $_SESSION['error'] = "Failed to save supplier: " . $stmt->error;
}
$stmt->close();

header("Location: suppliers.php");
exit;
?>
