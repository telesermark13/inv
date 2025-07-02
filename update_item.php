<?php
require_once __DIR__ . '/includes/auth.php'; // Adjust path as needed
require_once __DIR__ . '/includes/is_admin.php'; // Adjust path as needed
require_once __DIR__ . '/includes/db.php'; // Adjust path as needed
file_put_contents(__DIR__.'/debug_ajax.log', print_r($_POST, true), FILE_APPEND);
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Generate CSRF token if not already set in session (should be done on pages displaying forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // This is more of a fallback; token should exist from the form page.
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('CSRF validation failed', 403);
    }

    // Fields expected from the form (all should be present now with the modal changes)
    $required = ['id', 'name', 'quantity', 'unit', 'unit_price', 'sku', 'min_stock_level'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) { // SKU and min_stock_level can be empty string, but must be set
            throw new Exception("Missing field: $field", 400);
        }
    }

    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']); // SKU can be empty string if not provided

    // Handle description: if empty string, store as NULL, otherwise trim.
    $description_raw = trim($_POST['description'] ?? '');
    $description = ($description_raw === '') ? null : $description_raw;
    $min_stock_level = isset($_POST['min_stock_level']) ? (int)$_POST['min_stock_level'] : 0;

    $quantity = (int)$_POST['quantity'];
    $unit = $_POST['unit'];
    $unit_price = (float)$_POST['unit_price'];
    $min_stock_level = (int)$_POST['min_stock_level'];
    $taxable = isset($_POST['taxable']) ? 1 : 0; // 'taxable' checkbox sends value 'on' or similar if checked

    if ($id <= 0) throw new Exception("Invalid Item ID.", 400);
    if (empty($name)) throw new Exception("Item name cannot be empty.", 400);
    if ($quantity < 0) throw new Exception("Quantity cannot be negative.", 400);
    if ($unit_price <= 0) throw new Exception("Unit price must be positive.", 400);
    if ($min_stock_level < 0) throw new Exception("Minimum stock level cannot be negative.", 400);


    // Calculate prices
    $price_nontaxed = $unit_price; // Base unit price is non-taxed
    $price_taxed = $taxable ? round($unit_price * 1.12, 2) : $price_nontaxed;

   $stmt = $conn->prepare("UPDATE items SET
    name = ?,
    sku = ?,
    description = ?,
    unit = ?,
    quantity = ?,
    unit_price = ?,
    price_taxed = ?,
    price_nontaxed = ?,
    min_stock_level = ?
    WHERE id = ?"
);

$stmt->bind_param("ssssiddddi", $name, $sku, $description, $unit, $quantity, $unit_price, $price_taxed, $price_nontaxed, $min_stock_level, $id);





    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error, 500);
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
    } else {
        // This could mean the item was not found, or no data actually changed.
        // Check if item exists to differentiate.
        $check_stmt = $conn->prepare("SELECT id FROM items WHERE id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $item_exists = $check_stmt->get_result()->num_rows > 0;
        $check_stmt->close();

        if (!$item_exists) {
            throw new Exception("Update failed: Item with ID $id not found.", 404);
        }
        echo json_encode(['success' => true, 'message' => 'No changes detected for the item.']);
    }
    $stmt->close();
} catch (Exception $e) {
    $errorCode = $e->getCode();
    if ($errorCode < 400 || $errorCode >= 600) { // Standard HTTP error codes are 4xx and 5xx
        $errorCode = 500; // Default to internal server error
    }
    http_response_code($errorCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>