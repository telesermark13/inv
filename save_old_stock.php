<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/is_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: old_stocks.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header("Location: old_stocks.php");
    exit;
}

// --- Validate and sanitize inputs ---
$old_stock_id = isset($_POST['old_stock_id_for_edit']) ? (int)$_POST['old_stock_id_for_edit'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$sku = isset($_POST['sku']) ? trim($_POST['sku']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$quantity_str = $_POST['quantity'] ?? '0';
$unit = isset($_POST['unit']) ? trim($_POST['unit']) : '';
$unit_price_str = $_POST['unit_price'] ?? '0';
$supplier_id = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$date_acquired_str = $_POST['date_acquired'] ?? '';
$min_stock_level_str = $_POST['min_stock_level'] ?? '10';

// Validation
if (empty($name)) {
    $_SESSION['error'] = "Old stock item name is required.";
    header("Location: old_stocks.php");
    exit;
}
if (!is_numeric($quantity_str) || (float)$quantity_str < 0) {
    $_SESSION['error'] = "Invalid quantity provided.";
    header("Location: old_stocks.php");
    exit;
}
$quantity = (float)$quantity_str;

if (!is_numeric($unit_price_str) || (float)$unit_price_str < 0) {
    $_SESSION['error'] = "Invalid unit price provided.";
    header("Location: old_stocks.php");
    exit;
}
$unit_price = (float)$unit_price_str;

if (empty($unit)) {
    $_SESSION['error'] = "Unit is required.";
    header("Location: old_stocks.php");
    exit;
}

if (!is_numeric($min_stock_level_str) || (int)$min_stock_level_str < 0) {
    $_SESSION['error'] = "Invalid min stock level.";
    header("Location: old_stocks.php");
    exit;
}
$min_stock_level = (int)$min_stock_level_str;

$date_acquired = null;
if (!empty($date_acquired_str)) {
    $date_acquired_timestamp = strtotime($date_acquired_str);
    if ($date_acquired_timestamp !== false) {
        $date_acquired = date('Y-m-d', $date_acquired_timestamp);
    } else {
        $_SESSION['error'] = "Invalid date acquired format. Please use YYYY-MM-DD.";
        header("Location: old_stocks.php");
        exit;
    }
} else {
    $date_acquired = date('Y-m-d');
}

$conn->begin_transaction();
try {
    if ($old_stock_id > 0) { // Update
        $stmt = $conn->prepare(
            "UPDATE `old_stocks` SET 
                `sku` = ?, `name` = ?, `description` = ?, `quantity` = ?, `unit` = ?, 
                `unit_price` = ?, `supplier_id` = ?, `date_acquired` = ?, `min_stock_level` = ?, `updated_at` = NOW()
             WHERE `id` = ?"
        );
        if (!$stmt) throw new Exception("Prepare update failed: " . $conn->error);
        $stmt->bind_param(
            "sssdsdisii",
            $sku, $name, $description, $quantity, $unit,
            $unit_price, $supplier_id, $date_acquired, $min_stock_level, $old_stock_id
        );
        $action_verb = "updated";

    } else { // Insert
        $stmt = $conn->prepare(
            "INSERT INTO `old_stocks` 
                (`sku`, `name`, `description`, `quantity`, `unit`, `unit_price`, `supplier_id`, `date_acquired`, `min_stock_level`, `created_at`, `updated_at`) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        if (!$stmt) throw new Exception("Prepare insert failed: " . $conn->error);
        $stmt->bind_param(
            "sssdsdisi",
            $sku, $name, $description, $quantity, $unit,
            $unit_price, $supplier_id, $date_acquired, $min_stock_level
        );
        $action_verb = "added";
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0 || ($old_stock_id > 0 && $stmt->affected_rows == 0)) {
        $_SESSION['success'] = "Old stock item {$action_verb} successfully.";
        $conn->commit();
    } else if ($old_stock_id == 0 && $conn->insert_id > 0) {
        $_SESSION['success'] = "Old stock item {$action_verb} successfully with ID: " . $conn->insert_id;
        $conn->commit();
    } else {
        $_SESSION['warning'] = "Old stock item operation completed, but no rows were changed in the database. Please verify.";
        $conn->commit();
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Save Old Stock Error: " . $e->getMessage());
    $_SESSION['error'] = "Error saving old stock item: " . $e->getMessage();
}

header("Location: old_stocks.php");
exit;
?>
