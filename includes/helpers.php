<?php
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
function ensure_master_item($conn, $master_item_id, $item_data) {
    $stmt = $conn->prepare("SELECT id FROM master_items WHERE id = ?");
    $stmt->bind_param("i", $master_item_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $stmt_insert = $conn->prepare("INSERT INTO master_items (id, name, description, sku, unit, category)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param(
            "isssss",
            $master_item_id,
            $item_data['name'],
            $item_data['description'],
            $item_data['sku'],
            $item_data['unit'],
            $item_data['category']
        );
        if (!$stmt_insert->execute()) {
            throw new Exception("Failed to auto-create master item: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    } else {
        $stmt->close();
    }
    return $master_item_id;
}
function log_inventory_movement($conn, $item_id, $type, $quantity, $ref_type, $ref_id, $user_id)
{
    $stmt = $conn->prepare("INSERT INTO inventory_movements 
                          (item_id, movement_type, quantity, reference_type, reference_id, user_id)
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isissi", $item_id, $type, $quantity, $ref_type, $ref_id, $user_id);
    return $stmt->execute();
}
function check_low_stock($conn)
{
    $result = $conn->query("SELECT name, quantity, min_stock_level 
                           FROM items 
                           WHERE quantity <= min_stock_level");
    return $result->fetch_all(MYSQLI_ASSOC);
}
