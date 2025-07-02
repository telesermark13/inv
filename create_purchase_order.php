<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_GET['request_id']) || !filter_var($_GET['request_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Invalid material request ID.";
    header("Location: materials_request_admin.php");
    exit;
}
$request_id = (int)$_GET['request_id'];

// 1. Fetch the approved material request
$stmt = $conn->prepare("SELECT * FROM materials_requests WHERE id = ? AND status = 'approved'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$mr = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mr) {
    $_SESSION['error'] = "Material request not found or not approved.";
    header("Location: materials_request_admin.php");
    exit;
}

// 2. Fetch all items for this material request
$stmt = $conn->prepare("SELECT * FROM materials_request_items WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$mr_items = $stmt->get_result();
if ($mr_items->num_rows == 0) {
    $_SESSION['error'] = "No items found for this material request.";
    header("Location: materials_request_admin.php");
    exit;
}

// 3. Start transaction
$conn->begin_transaction();

try {
    // 4. Insert new purchase order
    $supplier_id = $mr['supplier_id'] ?? null;
    $order_date = date('Y-m-d');
    $created_by = $_SESSION['user_id'];
    $status = 'pending_po_approval'; // or 'pending' depending on your flow

    $stmt = $conn->prepare("INSERT INTO purchase_orders (request_id, supplier_id, order_date, status, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissi", $request_id, $supplier_id, $order_date, $status, $created_by);
    $stmt->execute();
    $po_id = $stmt->insert_id;
    $stmt->close();

    // 5. Insert PO items
    $insert_stmt = $conn->prepare("INSERT INTO purchase_order_items (order_id, item_type, item_id, description, sku, unit, quantity, unit_price, taxable)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    while ($item = $mr_items->fetch_assoc()) {
        $item_type = $item['item_type'] ?? 'current'; // fallback if you have this field
        $item_id = $item['item_id'];
        $desc = $item['description'];
        $sku = $item['sku'];
        $unit = $item['unit'];
        $qty = $item['quantity_requested'];
        $price = $item['unit_price'];
        $taxable = $item['taxable'] ?? 0;

        $insert_stmt->bind_param("isisssddi", $po_id, $item_type, $item_id, $desc, $sku, $unit, $qty, $price, $taxable);
        $insert_stmt->execute();
    }
    $insert_stmt->close();

    // 6. Update material request to link to PO (optional, or set status if desired)
    $stmt = $conn->prepare("UPDATE materials_requests SET status = 'processed', processed_by = ?, processed_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $created_by, $request_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $_SESSION['success'] = "Purchase Order created successfully!";
    header("Location: purchase_orders.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error creating purchase order: " . $e->getMessage();
    header("Location: materials_request_admin.php");
    exit;
}
?>
