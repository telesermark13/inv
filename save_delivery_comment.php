<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_number = intval($_POST['delivery_number']);
    $comments = trim($_POST['comments']);

    if ($delivery_number > 0) {
        $stmt = $conn->prepare("UPDATE delivery_receipts SET comments=? WHERE delivery_number=?");
        $stmt->bind_param("si", $comments, $delivery_number);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Comment saved!";
    } else {
        $_SESSION['error'] = "Invalid delivery number!";
    }
    header("Location: delivery_history.php");
    exit;
}
?>
