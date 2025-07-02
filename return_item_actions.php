<?php
if (ob_get_level() == 0) ob_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$view = __DIR__ . '/views/returned_items_page.php';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Only allow admin/manager
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    $_SESSION['error'] = "Unauthorized action.";
    header("Location: " . BASE_URL . "returned_items_page.php");
    exit;
}

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['error'] = "CSRF token validation failed.";
        header("Location: " . BASE_URL . "returned_items_page.php");
        exit;
    }
}

$action = $_REQUEST['action'] ?? null;

if ($action === 'add_return' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $item_id = (int)$_POST['item_id'];
        $name_at_return = trim($_POST['name_at_return']);
        $sku_at_return = trim($_POST['sku_at_return']) ?: null;
        $description_at_return = trim($_POST['description_at_return']) ?: null;
        $unit_at_return = trim($_POST['unit_at_return']) ?: null;
        $unit_price_at_return = $_POST['unit_price_at_return'] !== '' ? (float)$_POST['unit_price_at_return'] : null;
        $price_taxed_at_return = $_POST['price_taxed_at_return'] !== '' ? (float)$_POST['price_taxed_at_return'] : null;
        $price_nontaxed_at_return = $_POST['price_nontaxed_at_return'] !== '' ? (float)$_POST['price_nontaxed_at_return'] : null;
        $quantity_returned = (int)$_POST['quantity_returned'];
        $reason = trim($_POST['reason']) ?: null;
        $returned_by = !empty($_POST['returned_by']) ? (int)$_POST['returned_by'] : null;
        $received_by = (int)$_POST['received_by'];
        $customer_name = trim($_POST['customer_name'] ?? '') ?: null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $return_date = $_POST['return_date'];

        if ($item_id <= 0 || $quantity_returned <= 0 || $received_by <= 0 || empty($return_date) || empty($name_at_return)) {
            throw new Exception("Missing required fields or invalid data for adding return.");
        }
        $return_date_mysql = date('Y-m-d H:i:s', strtotime($return_date));

        // Insert the return record
        $stmt = $conn->prepare("INSERT INTO returned_items (item_id, name_at_return, sku_at_return, description_at_return, unit_at_return, unit_price_at_return, price_taxed_at_return, price_nontaxed_at_return, quantity_returned, reason, returned_by, received_by, customer_name, location, return_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare insert failed: (".$conn->errno.") ".$conn->error);
        $stmt->bind_param("isssssdddisisss", $item_id, $name_at_return, $sku_at_return, $description_at_return, $unit_at_return, $unit_price_at_return, $price_taxed_at_return, $price_nontaxed_at_return, $quantity_returned, $reason, $returned_by, $received_by, $customer_name, $location, $return_date_mysql);
        if (!$stmt->execute()) throw new Exception("Execute insert failed: (".$stmt->errno.") ".$stmt->error);
        $stmt->close();

        // Update stock
        $stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare stock update failed: (".$conn->errno.") ".$conn->error);
        $stmt->bind_param("ii", $quantity_returned, $item_id);
        if (!$stmt->execute()) throw new Exception("Execute stock update failed: (".$stmt->errno.") ".$stmt->error);
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Item return recorded successfully and stock updated.";
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => "Item return recorded successfully and stock updated."]);
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Database transaction failed: " . $e->getMessage();
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Database transaction failed: " . $e->getMessage()]);
            exit;
        }
    }
    header("Location: " . BASE_URL . "returned_items_page.php");
    exit;
}



// --- ACTION: Get Return Details for Edit Modal (AJAX GET) ---
if ($action === 'get_return_details' && isset($_GET['return_id'])) {
    header('Content-Type: application/json');
    $return_id = (int)$_GET['return_id'];
    $stmt = $conn->prepare("SELECT * FROM returned_items WHERE id = ?");
    if(!$stmt) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Prepare failed: ('.$conn->errno.') '.$conn->error]); exit; }
    $stmt->bind_param("i", $return_id);
    if(!$stmt->execute()) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Execute failed: ('.$stmt->errno.') '.$stmt->error]); exit; }
    $result = $stmt->get_result();
    if ($record = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $record]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Return record not found.']);
    }
    $stmt->close();
    exit;
}

// --- POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        if ($action === 'add_return') {
            $item_id = (int)$_POST['item_id'];
            $name_at_return = trim($_POST['name_at_return']);
            $sku_at_return = trim($_POST['sku_at_return']) ?: null;
            $description_at_return = trim($_POST['description_at_return']) ?: null;
            $unit_at_return = trim($_POST['unit_at_return']) ?: null;
            $unit_price_at_return = !empty($_POST['unit_price_at_return']) ? (float)$_POST['unit_price_at_return'] : null;
            $price_taxed_at_return = !empty($_POST['price_taxed_at_return']) ? (float)$_POST['price_taxed_at_return'] : null;
            $price_nontaxed_at_return = !empty($_POST['price_nontaxed_at_return']) ? (float)$_POST['price_nontaxed_at_return'] : null;
            $quantity_returned = (int)$_POST['quantity_returned'];
            $reason = trim($_POST['reason']) ?: null;
            $returned_by = !empty($_POST['returned_by']) ? (int)$_POST['returned_by'] : null;
            $received_by = (int)$_POST['received_by'];
            $customer_name = trim($_POST['customer_name'] ?? '') ?: null;
            $location = trim($_POST['location'] ?? '') ?: null;
            $return_date = $_POST['return_date'];

            if ($item_id <= 0 || $quantity_returned <= 0 || $received_by <= 0 || empty($return_date) || empty($name_at_return)) {
                throw new Exception("Missing required fields or invalid data for adding return.");
            }
            $return_date_mysql = date('Y-m-d H:i:s', strtotime($return_date));

            $stmt_insert = $conn->prepare("INSERT INTO returned_items (item_id, name_at_return, sku_at_return, description_at_return, unit_at_return, unit_price_at_return, price_taxed_at_return, price_nontaxed_at_return, quantity_returned, reason, returned_by, received_by, customer_name, location, return_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if(!$stmt_insert) throw new Exception("Prepare insert failed: (".$conn->errno.") ".$conn->error);
            $stmt_insert->bind_param("isssssdddisisss", $item_id, $name_at_return, $sku_at_return, $description_at_return, $unit_at_return, $unit_price_at_return, $price_taxed_at_return, $price_nontaxed_at_return, $quantity_returned, $reason, $returned_by, $received_by, $customer_name, $location, $return_date_mysql);
            if(!$stmt_insert->execute()) throw new Exception("Execute insert failed: (".$stmt_insert->errno.") ".$stmt_insert->error);
            $stmt_insert->close();

            $stmt_stock = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
            if(!$stmt_stock) throw new Exception("Prepare stock update failed: (".$conn->errno.") ".$conn->error);
            $stmt_stock->bind_param("ii", $quantity_returned, $item_id);
            if(!$stmt_stock->execute()) throw new Exception("Execute stock update failed: (".$stmt_stock->errno.") ".$stmt_stock->error);
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Failed to update stock for item ID: $item_id. Item may not exist or quantity unchanged.");
            }
            $stmt_stock->close();

            $conn->commit();
            $_SESSION['success'] = "Item return recorded successfully and stock updated.";

        } elseif ($action === 'update_return') {
            $return_id = (int)$_POST['return_id'];
            $original_item_id = (int)$_POST['original_item_id_for_edit'];
            $new_quantity_returned = (int)$_POST['quantity_returned'];
            $new_reason = trim($_POST['reason']) ?: null;
            $new_returned_by = !empty($_POST['returned_by']) ? (int)$_POST['returned_by'] : null;
            $new_received_by = (int)$_POST['received_by'];
            $new_customer_name = trim($_POST['customer_name'] ?? '') ?: null;
            $new_location = trim($_POST['location'] ?? '') ?: null;
            $new_return_date = $_POST['return_date'];

            if ($return_id <= 0 || $original_item_id <= 0 || $new_quantity_returned <= 0 || $new_received_by <= 0 || empty($new_return_date)) {
                throw new Exception("Missing required fields or invalid data for updating return.");
            }
            $new_return_date_mysql = date('Y-m-d H:i:s', strtotime($new_return_date));

            $stmt_orig = $conn->prepare("SELECT quantity_returned FROM returned_items WHERE id = ? AND item_id = ?");
            if(!$stmt_orig) throw new Exception("Prepare select original failed: (".$conn->errno.") ".$conn->error);
            $stmt_orig->bind_param("ii", $return_id, $original_item_id);
            if(!$stmt_orig->execute()) throw new Exception("Execute select original failed: (".$stmt_orig->errno.") ".$stmt_orig->error);
            $result_orig = $stmt_orig->get_result();
            if (!($original_data = $result_orig->fetch_assoc())) {
                throw new Exception("Original return record not found or item ID mismatch.");
            }
            $original_quantity_returned = (int)$original_data['quantity_returned'];
            $stmt_orig->close();
            $quantity_difference = $new_quantity_returned - $original_quantity_returned;

            $stmt_update = $conn->prepare("UPDATE returned_items SET quantity_returned = ?, reason = ?, returned_by = ?, received_by = ?, customer_name = ?, location = ?, return_date = ? WHERE id = ?");
            if(!$stmt_update) throw new Exception("Prepare update failed: (".$conn->errno.") ".$conn->error);
            $stmt_update->bind_param("isiisssi", $new_quantity_returned, $new_reason, $new_returned_by, $new_received_by, $new_customer_name, $new_location, $new_return_date_mysql, $return_id);
            if(!$stmt_update->execute()) throw new Exception("Execute update failed: (".$stmt_update->errno.") ".$stmt_update->error);
            $stmt_update->close();

            if ($quantity_difference != 0) {
                $stmt_stock_update = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                if(!$stmt_stock_update) throw new Exception("Prepare stock update (diff) failed: (".$conn->errno.") ".$conn->error);
                $stmt_stock_update->bind_param("ii", $quantity_difference, $original_item_id);
                if(!$stmt_stock_update->execute()) throw new Exception("Execute stock update (diff) failed: (".$stmt_stock_update->errno.") ".$stmt_stock_update->error);
                if ($stmt_stock_update->affected_rows === 0 && $original_item_id > 0) {
                    $checkItemExistsStmt = $conn->prepare("SELECT id FROM items WHERE id = ?");
                    $checkItemExistsStmt->bind_param("i", $original_item_id);
                    $checkItemExistsStmt->execute();
                    if($checkItemExistsStmt->get_result()->num_rows === 0){
                        throw new Exception("Failed to update stock for item ID: $original_item_id. Item no longer exists.");
                    }
                    $checkItemExistsStmt->close();
                }
                $stmt_stock_update->close();
            }
            $conn->commit();
            $_SESSION['success'] = "Return record updated successfully and stock adjusted.";

        } elseif ($action === 'delete_return') {
            $return_id = (int)$_POST['return_id'];
            if ($return_id <= 0) throw new Exception("Invalid return ID for deletion.");

            $stmt_orig_delete = $conn->prepare("SELECT item_id, quantity_returned FROM returned_items WHERE id = ?");
            if(!$stmt_orig_delete) throw new Exception("Prepare select for delete failed: (".$conn->errno.") ".$conn->error);
            $stmt_orig_delete->bind_param("i", $return_id);
            if(!$stmt_orig_delete->execute()) throw new Exception("Execute select for delete failed: (".$stmt_orig_delete->errno.") ".$stmt_orig_delete->error);
            $result_orig_delete = $stmt_orig_delete->get_result();
            if (!($data_to_delete = $result_orig_delete->fetch_assoc())) {
                throw new Exception("Return record not found for deletion.");
            }
            $item_id_to_adjust = (int)$data_to_delete['item_id'];
            $quantity_to_revert = (int)$data_to_delete['quantity_returned'];
            $stmt_orig_delete->close();

            $stmt_delete = $conn->prepare("DELETE FROM returned_items WHERE id = ?");
            if(!$stmt_delete) throw new Exception("Prepare delete failed: (".$conn->errno.") ".$conn->error);
            $stmt_delete->bind_param("i", $return_id);
            if(!$stmt_delete->execute()) throw new Exception("Execute delete failed: (".$stmt_delete->errno.") ".$stmt_delete->error);
            $stmt_delete->close();

            $stmt_stock_revert = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
            if(!$stmt_stock_revert) throw new Exception("Prepare stock revert failed: (".$conn->errno.") ".$conn->error);
            $stmt_stock_revert->bind_param("ii", $quantity_to_revert, $item_id_to_adjust);
            if(!$stmt_stock_revert->execute()) throw new Exception("Execute stock revert failed: (".$stmt_stock_revert->errno.") ".$stmt_stock_revert->error);
            if ($stmt_stock_revert->affected_rows === 0 && $item_id_to_adjust > 0) {
                $checkItemExistsStmtDel = $conn->prepare("SELECT id FROM items WHERE id = ?");
                $checkItemExistsStmtDel->bind_param("i", $item_id_to_adjust);
                $checkItemExistsStmtDel->execute();
                if($checkItemExistsStmtDel->get_result()->num_rows === 0){
                    throw new Exception("Failed to revert stock for item ID: $item_id_to_adjust. Item no longer exists.");
                }
                $checkItemExistsStmtDel->close();
            }
            $stmt_stock_revert->close();
            $conn->commit();
            $_SESSION['success'] = "Return record deleted successfully and stock reverted.";
        } else {
            throw new Exception("Invalid POST action specified.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Database transaction failed: " . $e->getMessage();
    }
    header("Location: " . BASE_URL . "returned_items_page.php");
    exit;
}

if ($action) {
    $_SESSION['error'] = "Unsupported request method for the specified action.";
    header("Location: " . BASE_URL . "returned_items_page.php");
    exit;
}

$_SESSION['error'] = "No action specified or invalid request.";
header("Location: " . BASE_URL . "dashboard.php");
exit;

if (ob_get_level() > 0) ob_end_flush();
?>