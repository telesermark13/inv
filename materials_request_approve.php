<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: materials_request_admin.php");
    exit;
}

$request_id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($request_id <= 0) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: materials_request_admin.php");
    exit;
}

if ($action === "deny") {
    $conn->query("UPDATE materials_requests SET status = 'denied', processed_by = {$_SESSION['user_id']}, processed_at = NOW() WHERE id = $request_id");
    $_SESSION['success'] = "Request denied.";
    header("Location: materials_request_admin.php");
    exit;
}

if ($action !== "approve") {
    $_SESSION['error'] = "Invalid action.";
    header("Location: materials_request_admin.php");
    exit;
}

// Approve the request and create Purchase Order automatically
$conn->begin_transaction();
try {
    // Approve the Material Request
    $conn->query("UPDATE materials_requests SET status = 'approved', processed_by = {$_SESSION['user_id']}, processed_at = NOW() WHERE id = $request_id");

    // Fetch the MR header for supplier/user
    $mr = $conn->query("SELECT * FROM materials_requests WHERE id = $request_id")->fetch_assoc();
    if (!$mr) throw new Exception("Material Request not found.");

    $supplier_id = (int)($mr['supplier_id'] ?? 0);
    $created_by = (int)($mr['user_id'] ?? 0);
    $mr_date = $mr['request_date'] ?? date('Y-m-d H:i:s');

    // Insert PO header
    $stmt = $conn->prepare(
        "INSERT INTO purchase_orders (supplier_id, request_id, status, order_date, created_by, updated_at)
         VALUES (?, ?, 'pending', ?, ?, NOW())"
    );
    $stmt->bind_param("iisi", $supplier_id, $request_id, $mr_date, $created_by);
    $stmt->execute();
    $po_id = $conn->insert_id;
    $stmt->close();

    // Fetch MR items
    $items = $conn->query("SELECT * FROM materials_request_items WHERE request_id = $request_id");
    while ($item = $items->fetch_assoc()) {
        // Try to find the item in items table (by SKU, or fallback to Name)
        $item_id = 0;
        $find = $conn->prepare("SELECT id FROM items WHERE sku = ? LIMIT 1");
        $find->bind_param("s", $item['sku']);
        $find->execute();
        $find_result = $find->get_result();

        if ($row = $find_result->fetch_assoc()) {
            $item_id = $row['id'];
        } else {
            // Not found by SKU, try by name
            $find2 = $conn->prepare("SELECT id FROM items WHERE name = ? LIMIT 1");
            $find2->bind_param("s", $item['name']);
            $find2->execute();
            $find2_result = $find2->get_result();
            if ($row2 = $find2_result->fetch_assoc()) {
                $item_id = $row2['id'];
            } else {
                // Not found: insert new into items table (NO status column)
                $insert = $conn->prepare("
                    INSERT INTO items (master_item_id, name, description, sku, unit, unit_price, quantity, price_taxed, price_nontaxed, taxable)
                    VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 0)
                ");
                $insert->bind_param(
                    "issssd",
                    $item['master_item_id'],
                    $item['name'],
                    $item['description'],
                    $item['sku'],
                    $item['unit'],
                    $item['unit_price']
                );
                $insert->execute();
                $item_id = $conn->insert_id;
                $insert->close();
            }
            $find2->close();
        }
        $find->close();

        // Insert into purchase_order_items with the correct items.id as item_id
        $stmt2 = $conn->prepare(
            "INSERT INTO purchase_order_items (order_id, item_type, item_id, master_item_id, description, sku, unit, quantity, unit_price, taxable, quantity_received)
             VALUES (?, 'current', ?, ?, ?, ?, ?, ?, ?, ?, 0)"
        );
        $stmt2->bind_param(
            "iiisssddi",
            $po_id,
            $item_id,
            $item['master_item_id'],
            $item['description'],
            $item['sku'],
            $item['unit'],
            $item['quantity'],
            $item['unit_price'],
            $item['taxable']
        );
        $stmt2->execute();
        $stmt2->close();
    }

    $conn->commit();
    $_SESSION['success'] = "Request approved, inventory updated, and Purchase Order automatically created!";
    header("Location: materials_request_admin.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    header("Location: materials_request_admin.php");
    exit;
}
?>
