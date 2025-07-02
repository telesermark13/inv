<?php

require_once __DIR__ . '/includes/auth.php';
// require_once __DIR__ . '/includes/is_admin.php'; // Or purchasing role
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php'; // For log_inventory_movement

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: purchase_orders.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header("Location: purchase_orders.php");
    exit;
}

if (!isset($_POST['po_id']) || !filter_var($_POST['po_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Missing or invalid Purchase Order ID.";
    header("Location: purchase_orders.php");
    exit;
}
$po_id = (int)$_POST['po_id'];

if (empty($_POST['received_date'])) {
    $_SESSION['error'] = "Received date is required.";
    header("Location: receive_purchase_order.php?po_id=" . $po_id);
    exit;
}
$received_date_str = $_POST['received_date'];
$received_date = date('Y-m-d H:i:s', strtotime($received_date_str));
$supplier_dr_no = isset($_POST['supplier_dr_no']) ? trim($_POST['supplier_dr_no']) : null;
$receiving_notes = isset($_POST['receiving_notes']) ? trim($_POST['receiving_notes']) : null;
$received_by_user_id = (int)($_POST['received_by_user_id'] ?? $_SESSION['user_id']);
$sales_invoice_no = trim($_POST['sales_invoice_no'] ?? '');

$submitted_items = $_POST['items'] ?? [];
if (empty($submitted_items)) {
    $_SESSION['warning'] = "No items were marked as received.";
    header("Location: receive_purchase_order.php?po_id=" . $po_id);
    exit;
}

$conn->begin_transaction();
try {
    $at_least_one_item_received_this_time = false;

    // 1. Iterate through submitted items and update stock
    foreach ($submitted_items as $item_data) {
        $po_item_id = (int)$item_data['po_item_id'];
        $qty_receiving_now = (float)($item_data['qty_receiving_now'] ?? 0);

        if ($qty_receiving_now <= 0) continue; // Skip if not receiving this item now

        $at_least_one_item_received_this_time = true;
        $master_item_id = isset($item_data['master_item_id']) && !empty($item_data['master_item_id']) ? (int)$item_data['master_item_id'] : null;
        $master_item_type = $item_data['master_item_type'] ?? 'current';

        // Fetch original ordered quantity and current received quantity for validation
        $stmt_check_item = $conn->prepare("SELECT quantity, quantity_received FROM purchase_order_items WHERE id = ? AND order_id = ?");
        if (!$stmt_check_item) throw new Exception("Prepare check item failed: " . $conn->error);
        $stmt_check_item->bind_param("ii", $po_item_id, $po_id);
        $stmt_check_item->execute();
        $po_item_db = $stmt_check_item->get_result()->fetch_assoc();
        $stmt_check_item->close();

        if (!$po_item_db) throw new Exception("PO Item ID {$po_item_id} not found for PO #{$po_id}.");

        $ordered_qty_db = (float)$po_item_db['quantity'];
        $already_received_qty_db = (float)$po_item_db['quantity_received'];
        $max_receivable_now = $ordered_qty_db - $already_received_qty_db;

        if ($qty_receiving_now > $max_receivable_now) {
            throw new Exception("Cannot receive {$qty_receiving_now} for PO Item ID {$po_item_id}. Max receivable is {$max_receivable_now}.");
        }

        // ========================
        // INSERT OR UPDATE LOGIC
        // ========================
        if ($master_item_id && $qty_receiving_now > 0) {
            $target_table = ($master_item_type === 'old') ? 'old_stocks' : 'items';

            // Try to update quantity first (existing item in inventory)
            $stmt_update_stock = $conn->prepare("UPDATE `{$target_table}` SET quantity = quantity + ? WHERE id = ?");
            if (!$stmt_update_stock) throw new Exception("Prepare stock update failed for {$target_table}: " . $conn->error);
            $stmt_update_stock->bind_param("di", $qty_receiving_now, $master_item_id);
            $stmt_update_stock->execute();

            if ($stmt_update_stock->affected_rows === 0 && $target_table === 'items') {
                // Item does not exist in `items`, so copy all details from master_items to items
                $stmt_update_stock->close();

                // Fetch details from master_items
                $stmt_fetch_master = $conn->prepare("SELECT * FROM master_items WHERE id = ?");
                $stmt_fetch_master->bind_param("i", $master_item_id);
                $stmt_fetch_master->execute();
                $master_item_data = $stmt_fetch_master->get_result()->fetch_assoc();
                $stmt_fetch_master->close();

                if (!$master_item_data) {
                    throw new Exception("master_items ID {$master_item_id} not found.");
                }

                // Insert new row in items table (copying everything from master_items)
                $stmt_insert_item = $conn->prepare("
                    INSERT INTO items (name, description, sku, unit, quantity, min_stock_level, unit_price, price_taxed, price_nontaxed, category)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_insert_item->bind_param(
                    "ssssidddds",
                    $master_item_data['name'],
                    $master_item_data['description'],
                    $master_item_data['sku'],
                    $master_item_data['unit'],
                    $qty_receiving_now,
                    $master_item_data['min_stock_level'],
                    $master_item_data['unit_price'],
                    $master_item_data['price_taxed'],
                    $master_item_data['price_nontaxed'],
                    $master_item_data['category']
                );
                if (!$stmt_insert_item->execute()) {
                    throw new Exception("Failed to insert new item into items: " . $stmt_insert_item->error);
                }
                $stmt_insert_item->close();

                // Log inventory movement for the new item
                $new_item_id = $conn->insert_id;
                log_inventory_movement($conn, $new_item_id, 'in', $qty_receiving_now, 'purchase', $po_id, $received_by_user_id);

            } else {
                // Existing item, update succeeded
                $stmt_update_stock->close();
                log_inventory_movement($conn, $master_item_id, 'in', $qty_receiving_now, 'purchase', $po_id, $received_by_user_id);
            }
        }

        // Update quantity_received in purchase_order_items
        $new_total_received_for_item = $already_received_qty_db + $qty_receiving_now;
        $stmt_update_poi = $conn->prepare("UPDATE purchase_order_items SET quantity_received = ? WHERE id = ?");
        if (!$stmt_update_poi) throw new Exception("Prepare update POI received qty failed: " . $conn->error);
        $stmt_update_poi->bind_param("di", $new_total_received_for_item, $po_item_id);
        if (!$stmt_update_poi->execute()) throw new Exception("Execute update POI received qty failed: " . $stmt_update_poi->error);
        $stmt_update_poi->close();
    }

    if (!$at_least_one_item_received_this_time) {
        $_SESSION['warning'] = "No quantities were entered for receiving.";
        $conn->rollback();
        header("Location: receive_purchase_order.php?po_id=" . $po_id);
        exit;
    }

    // 2. Update PO Header Status (fully_received or partially_received)
    $stmt_check_all_received = $conn->prepare(
        "SELECT SUM(quantity) as total_ordered, SUM(quantity_received) as total_actually_received
         FROM purchase_order_items WHERE order_id = ?"
    );
    if (!$stmt_check_all_received) throw new Exception("Prepare check all received failed: " . $conn->error);
    $stmt_check_all_received->bind_param("i", $po_id);
    $stmt_check_all_received->execute();
    $po_totals = $stmt_check_all_received->get_result()->fetch_assoc();
    $stmt_check_all_received->close();

    $new_po_status = 'partially_received';
    if ((float)$po_totals['total_actually_received'] >= (float)$po_totals['total_ordered']) {
        $new_po_status = 'fully_received';
    } elseif ((float)$po_totals['total_actually_received'] == 0) {
        $orig_po_status_stmt = $conn->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $orig_po_status_stmt->bind_param("i", $po_id); $orig_po_status_stmt->execute();
        $new_po_status = $orig_po_status_stmt->get_result()->fetch_assoc()['status'];
        $orig_po_status_stmt->close();
    }

    // Update PO status, notes, AND sales_invoice_no (fix)
    $stmt_update_po_status = $conn->prepare(
        "UPDATE purchase_orders SET status = ?, notes = CONCAT(COALESCE(notes,''), ?), sales_invoice_no = ?, updated_at = NOW() WHERE id = ?"
    );
    if (!$stmt_update_po_status) throw new Exception("Prepare update PO status failed: " . $conn->error);
    $appended_note = "\nReceived on {$received_date_str} by user ID {$received_by_user_id}. DR: {$supplier_dr_no}. Notes: {$receiving_notes}";
    $stmt_update_po_status->bind_param("sssi", $new_po_status, $appended_note, $sales_invoice_no, $po_id);
    if (!$stmt_update_po_status->execute()) throw new Exception("Execute update PO status failed: " . $stmt_update_po_status->error);
    $stmt_update_po_status->close();

    $conn->commit();

    $msg = "Goods for PO #{$po_id} received and inventory updated successfully.";
    $_SESSION['success'] = $msg;

    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        echo $msg;
        exit;
    } else {
        header("Location: purchase_orders.php?status=received_highlight&po_id=" . $po_id);
        exit;
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Process PO Receipt Error for PO #{$po_id}: " . $e->getMessage());
    $_SESSION['error'] = "Error processing receipt: " . $e->getMessage();

    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        http_response_code(500);
        echo "error: " . $e->getMessage();
        exit;
    } else {
        header("Location: receive_purchase_order.php?po_id=" . $po_id);
        exit;
    }
}
?>
