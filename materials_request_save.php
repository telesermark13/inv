<?php
file_put_contents('mr_debug.log', print_r($_POST, true));
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// CSRF validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header("Location: materials_request.php");
    exit;
}

// Validate at least one item
if (!isset($_POST['items']) || !is_array($_POST['items'])) {
    $_SESSION['error'] = "Please add at least one item.";
    header("Location: materials_request.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) {
    $_SESSION['error'] = "User session not found. Please login again.";
    header("Location: login.php");
    exit;
}

$supplier_id = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$request_date_str = $_POST['request_date'] ?? date('Y-m-d');
$request_date = date('Y-m-d H:i:s', strtotime($request_date_str));
$tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] / 100 : 0.12;

$conn->begin_transaction();

try {
    // Insert header
    $stmt = $conn->prepare(
        "INSERT INTO materials_requests (user_id, supplier_id, status, request_date, total_amount_nontaxed, total_tax_amount, grand_total_amount, processed_at)
         VALUES (?, ?, 'pending', ?, 0.00, 0.00, 0.00, NULL)"
    );
    $stmt->bind_param("iis", $user_id, $supplier_id, $request_date);
    $stmt->execute();
    $request_id = $conn->insert_id;
    $stmt->close();

    $sum_total_amount_nontaxed = 0;
    $sum_total_tax_amount = 0;
    $sum_grand_total_amount = 0;

    // Insert items
    $sql = "INSERT INTO materials_request_items
    (request_id, master_item_id, name, description, sku, unit, category, quantity, price, unit_price, taxable, subtotal, tax_amount, total)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql);

    foreach ($_POST['items'] as $item) {
        // Validate required fields
        if (
            empty($item['item_id']) || floatval($item['qty']) <= 0 ||
            empty($item['name']) || empty($item['category'])
        ) continue;

        $master_item_id = (int)$item['item_id'];
        $name = $item['name'];
        $description = $item['description'] ?? '';
        $sku = $item['sku'] ?? '';
        $unit = $item['unit'] ?? '';
        $category = $item['category'];
        $quantity = isset($item['qty']) ? (float)$item['qty'] : 0;
        // Unit price: use POST or lookup from master_items if you want always-fresh
        $unit_price = isset($item['price']) ? (float)$item['price'] : 0;
        $price = $unit_price; // You can store both, or just one and reference
        $taxable = !empty($item['taxable']) ? 1 : 0;

        $subtotal = $quantity * $unit_price;
        $tax_amount = $taxable ? $subtotal * $tax_rate : 0;
        $total = $subtotal + $tax_amount;

        // Totals for the summary
        $sum_total_amount_nontaxed += $subtotal;
        $sum_total_tax_amount += $tax_amount;
        $sum_grand_total_amount += $total;

        $stmt_item->bind_param(
            "iisssssddidddd",
            $request_id,
            $master_item_id,
            $name,
            $description,
            $sku,
            $unit,
            $category,
            $quantity,
            $price,
            $unit_price,
            $taxable,
            $subtotal,
            $tax_amount,
            $total
        );
        $stmt_item->execute();
    }
    $stmt_item->close();

    // Update totals
    $stmt_update = $conn->prepare(
        "UPDATE materials_requests
         SET total_amount_nontaxed = ?, total_tax_amount = ?, grand_total_amount = ?
         WHERE id = ?"
    );
    $stmt_update->bind_param("dddi", $sum_total_amount_nontaxed, $sum_total_tax_amount, $sum_grand_total_amount, $request_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    $_SESSION['success'] = "Material request #{$request_id} submitted successfully!";
    header("Location: materials_request.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Material Request Save Error: " . $e->getMessage());
    $_SESSION['error'] = "Error saving request: " . $e->getMessage();
    header("Location: materials_request.php");
    exit();
}
?>