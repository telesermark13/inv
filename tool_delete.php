<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    header('Location: index.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM tools WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}
header('Location: tools.php');
exit;
