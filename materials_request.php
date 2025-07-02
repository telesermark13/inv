<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$master_items = [];
$result = $conn->query("SELECT id, name, sku, unit, category, description FROM master_items ORDER BY name ASC");
while($row = $result->fetch_assoc()) {
    $master_items[] = $row;
}
// Only authenticated users can access this page
// (Remove the role check completely)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$view = 'views/materials_request_form.php';
include 'templates/layout.php';

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show">
            Request submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>