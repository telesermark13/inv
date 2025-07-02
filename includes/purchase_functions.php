<?php
function create_purchase_order($conn, $request_id, $supplier_id, $user_id, $initial_status = 'pending')
{
    $stmt_req_date = $conn->prepare("SELECT request_date FROM materials_requests WHERE id = ?");
    if (!$stmt_req_date) {
        error_log("Prepare select req date failed: " . $conn->error);
        return false;
    }
    $stmt_req_date->bind_param("i", $request_id);
    if (!$stmt_req_date->execute()) {
        error_log("Execute select req date failed: " . $stmt_req_date->error);
        $stmt_req_date->close();
        return false;
    }
    $result_req_date = $stmt_req_date->get_result();
    $mr_data = $result_req_date->fetch_assoc();
    $stmt_req_date->close();

    $order_date = $mr_data ? date('Y-m-d', strtotime($mr_data['request_date'])) : date('Y-m-d');

    // Add a notes field for the editable description the user wants for PO
    // You'll need to add `notes` TEXT NULL to your `purchase_orders` table schema if it's not there
    // ALTER TABLE `purchase_orders` ADD COLUMN `notes` TEXT NULL DEFAULT NULL AFTER `status_full`;
    $initial_notes = "Purchase Order automatically generated from Material Request #" . $request_id;

    $stmt = $conn->prepare(
        "INSERT INTO purchase_orders (request_id, supplier_id, created_by, status, order_date, notes, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->bind_param("iiisss", $request_id, $supplier_id, $user_id, $initial_status, $order_date, $initial_notes);
    if (!$stmt) {
        error_log("Prepare PO insert failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iiisss", $request_id, $supplier_id, $user_id, $initial_status, $order_date, $initial_notes);
    if ($stmt->execute()) {
        $po_id = $conn->insert_id;
        $stmt->close();
        return $po_id;
    } else {
        error_log("SQL Error in create_purchase_order: " . $stmt->errno . " - " . $stmt->error . " | SQL: INSERT INTO purchase_orders (...) VALUES (...)"); // Log the SQL too
        $stmt->close();
        return false;
    }
}


/**
 * Adds an item to a purchase order.
 * Now includes description, unit, sku for the PO item itself (snapshot)
 */
function add_purchase_order_item($conn, $order_id, $item_type, $item_id, $quantity, $unit_price, $description_snapshot, $unit_snapshot, $sku_snapshot = null)
{
    // ... (ALTER TABLE comments for adding description, sku, unit, quantity_received)
    $stmt = $conn->prepare(
        "INSERT INTO purchase_order_items (order_id, item_type, item_id, description, sku, quantity, unit, unit_price, quantity_received)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00)"
    );
    if (!$stmt) {
        error_log("Prepare add_purchase_order_item failed: " . $conn->error);
        return false;
    }

    $actual_item_id = ($item_id > 0) ? (int)$item_id : null; // Ensures NULL if $item_id is not a positive int

    $stmt->bind_param(
        "isisdsds", // order_id, item_type, actual_item_id, description, sku, quantity, unit, unit_price
        $order_id,
        $item_type,
        $actual_item_id, // Use the potentially NULL value here
        $description_snapshot,
        $sku_snapshot,
        $quantity,
        $unit_snapshot,
        $unit_price
    );
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("SQL Error in add_purchase_order_item for PO #{$order_id}: " . $stmt->errno . " - " . $stmt->error . " | Item Desc: {$description_snapshot}");
        $stmt->close();
        return false;
    }
}
