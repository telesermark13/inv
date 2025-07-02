<?php
// delivery_receipt.php (Controller)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';



$error = null;
$page_title = 'Create/Edit Delivery Receipt';

// 1. Prepare default form data
$is_edit_mode = isset($_GET['edit']) && is_numeric($_GET['edit']);
$delivery_number_from_get = $is_edit_mode ? (int)$_GET['edit'] : 0;

$form_data = [
    'is_edit' => $is_edit_mode,
    'delivery_number' => 0, // set below
    'existing_data' => null,
    'existing_items_details' => [],
    'previous_clients' => [],
    'previous_projects' => [],
    'previous_locations' => [],
    'all_inventory_items' => []
];

// 2. Fetch datalist options
$res = $conn->query("SELECT DISTINCT client FROM delivery_receipts WHERE client IS NOT NULL");
while ($row = $res->fetch_assoc()) $form_data['previous_clients'][] = $row['client'];
$res = $conn->query("SELECT DISTINCT project FROM delivery_receipts WHERE project IS NOT NULL");
while ($row = $res->fetch_assoc()) $form_data['previous_projects'][] = $row['project'];
$res = $conn->query("SELECT DISTINCT location FROM delivery_receipts WHERE location IS NOT NULL");
while ($row = $res->fetch_assoc()) $form_data['previous_locations'][] = $row['location'];

// 3. Fetch all inventory items (from items and old_stocks)
$res = $conn->query("SELECT id, name, sku, unit, quantity, description, unit_price, price_taxed, price_nontaxed FROM items");
while ($item = $res->fetch_assoc()) $form_data['all_inventory_items'][] = array_merge($item, ['type' => 'current']);
$res = $conn->query("SELECT id, name, sku, unit, quantity, description, unit_price, price_taxed, price_nontaxed FROM old_stocks");
while ($item = $res->fetch_assoc()) $form_data['all_inventory_items'][] = array_merge($item, ['type' => 'old']);

// 4. Edit mode: fetch existing header and items
if ($is_edit_mode) {
    $form_data['delivery_number'] = $delivery_number_from_get;
    $q = $conn->prepare("SELECT * FROM delivery_receipts WHERE delivery_number=?");
    $q->bind_param("i", $delivery_number_from_get);
    $q->execute();
    $form_data['existing_data'] = $q->get_result()->fetch_assoc();
    $q->close();

    $q = $conn->prepare("SELECT * FROM delivered_items WHERE delivery_number=?");
    $q->bind_param("i", $delivery_number_from_get);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) $form_data['existing_items_details'][] = $row;
    $q->close();

    if (!$form_data['existing_data']) {
        $form_data['is_edit'] = false;
        $_SESSION['error'] = "Delivery Receipt not found.";
    }
}

// 5. Assign next delivery number for new
if (!$form_data['is_edit']) {
    $r = $conn->query("SELECT MAX(delivery_number) as max_dn FROM delivery_receipts");
    $row = $r->fetch_assoc();
    $form_data['delivery_number'] = $row['max_dn'] ? $row['max_dn'] + 1 : 1000;
}

// 6. Handle POST (submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "CSRF token validation failed.";
        header("Location: delivery_receipt.php" . ($is_edit_mode ? "?edit=" . $delivery_number_from_get : ""));
        exit;
    }

    $is_edit_from_post = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';
    $delivery_number_from_post = (int)$_POST['delivery_number_hidden'];

    $client = trim($_POST['client']);
    $project = trim($_POST['project']);
    $location = trim($_POST['location']);
    $date_str = $_POST['date'];
    $delivery_date = date('Y-m-d', strtotime($date_str));
    $received_by_name = trim($_POST['received_by']);
    $prepared_by_user_id = $_SESSION['user_id'];

    if (empty($client) || empty($project) || empty($location) || empty($date_str)) {
        $_SESSION['error'] = "All header fields are required.";
        header("Location: delivery_receipt.php" . ($is_edit_mode ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }

    $submitted_line_items = $_POST['line_items'] ?? [];
    if (empty($submitted_line_items)) {
        $_SESSION['error'] = "At least one item must be added to the delivery receipt.";
        header("Location: delivery_receipt.php" . ($is_edit_mode ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }

    $conn->begin_transaction();
    try {
        $total_qty_delivered_overall = 0;
        $total_outstanding_overall = 0;

        // --- HEADER FIRST ---
        if ($is_edit_from_post) {
            $stmt = $conn->prepare(
                "UPDATE delivery_receipts SET client=?, project=?, location=?, date=?, received_by=?, total_quantity=?, outstanding=?, prepared_by=?, is_completed=? WHERE delivery_number=?"
            );
            $tmp_qty = 0;
            $tmp_outstanding = 0;
            $tmp_completed = 0; // set after items
            $stmt->bind_param(
                "sssssddiii",
                $client,
                $project,
                $location,
                $delivery_date,
                $received_by_name,
                $tmp_qty,
                $tmp_outstanding,
                $prepared_by_user_id,
                $tmp_completed,
                $delivery_number_from_post
            );
            if (!$stmt->execute()) throw new Exception("Header update failed: " . $stmt->error);
            $stmt->close();
        } else {
            $receipt_number_text = 'DR-' . date('Ymd') . '-' . $delivery_number_from_post;
            $stmt = $conn->prepare(
                "INSERT INTO delivery_receipts (receipt_number, delivery_number, client, project, location, date, prepared_by, received_by, total_quantity, outstanding, is_completed, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $tmp_qty = 0;
            $tmp_outstanding = 0;
            $tmp_completed = 0; // set after items
            $stmt->bind_param(
                "sissssisddi",
                $receipt_number_text,
                $delivery_number_from_post,
                $client,
                $project,
                $location,
                $delivery_date,
                $prepared_by_user_id,
                $received_by_name,
                $tmp_qty,
                $tmp_outstanding,
                $tmp_completed
            );
            if (!$stmt->execute()) throw new Exception("Header insert failed: " . $stmt->error);
            $stmt->close();
        }

        // --- DELETE OLD ITEMS IF EDIT ---
        if ($is_edit_from_post) {
            $stmt = $conn->prepare("DELETE FROM delivered_items WHERE delivery_number=?");
            $stmt->bind_param("i", $delivery_number_from_post);
            $stmt->execute();
            $stmt->close();
        }

        // --- INSERT delivered_items ---
        foreach ($submitted_line_items as $idx => $line_item) {
            $item_desc_text = trim($line_item['description']);
            $ordered_val = (float)($line_item['ordered'] ?? 0);
            $delivered_val = (float)($line_item['delivered'] ?? 0);
            $outstanding_val = $ordered_val - $delivered_val;
            $unit_text = trim($line_item['unit']);
            $unit_price = (float)($line_item['unit_price'] ?? 0);
            $price_taxed = (float)($line_item['price_taxed'] ?? 0);
            $price_nontaxed = (float)($line_item['price_nontaxed'] ?? 0);
            $total_nontaxed = $delivered_val * $price_nontaxed;

 $item_id = null;
if (!empty($line_item['master_item_full_id']) && strpos($line_item['master_item_full_id'], '_') !== false) {
    list($type, $extracted_item_id) = explode('_', $line_item['master_item_full_id']);
    $item_id = is_numeric($extracted_item_id) ? (int)$extracted_item_id : null;
}

// Prepare SQL Insert
$stmt = $conn->prepare(
    "INSERT INTO delivered_items 
        (delivery_number, item_id, item_description, ordered, delivered, outstanding, unit, unit_price, price_taxed, price_nontaxed, total_nontaxed, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

// Bind parameters correctly matching data types
$stmt->bind_param(
    "iisdddssddd",
    $delivery_number_from_post,
    $item_id,
    $item_desc_text,
    $ordered_val,
    $delivered_val,
    $outstanding_val,
    $unit_text,
    $unit_price,
    $price_taxed,
    $price_nontaxed,
    $total_nontaxed
);

// Execute statement
if (!$stmt->execute()) {
    throw new Exception("Delivered item insert failed: " . $stmt->error);
}
$stmt->close();


            // --- STOCK DEDUCTION START ---
            $master_item_full_id = $line_item['master_item_full_id'] ?? '';
            if ($master_item_full_id && $delivered_val > 0) {
                list($item_type, $item_id) = explode('_', $master_item_full_id);
                $item_id = (int)$item_id;
                if ($item_type === 'current') {
                    $update_qty = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                    $update_qty->bind_param("di", $delivered_val, $item_id);
                    $update_qty->execute();
                    $update_qty->close();
                } elseif ($item_type === 'old') {
                    $update_qty = $conn->prepare("UPDATE old_stocks SET quantity = quantity - ? WHERE id = ?");
                    $update_qty->bind_param("di", $delivered_val, $item_id);
                    $update_qty->execute();
                    $update_qty->close();
                }
            }
            // --- STOCK DEDUCTION END ---

            $total_qty_delivered_overall += $delivered_val;
            $total_outstanding_overall += $outstanding_val;
        }

        // --- UPDATE HEADER WITH TOTALS ---
        $is_completed_val = ($total_outstanding_overall == 0) ? 1 : 0;
        $stmt = $conn->prepare(
            "UPDATE delivery_receipts SET total_quantity=?, outstanding=?, is_completed=? WHERE delivery_number=?"
        );
        $stmt->bind_param("diii", $total_qty_delivered_overall, $total_outstanding_overall, $is_completed_val, $delivery_number_from_post);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Delivery receipt successfully created!";
        header("Location: delivery_receipt.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delivery Receipt Save Error for DN #{$delivery_number_from_post}: " . $e->getMessage());
        $_SESSION['error'] = "Error saving delivery receipt: " . $e->getMessage();
        header("Location: delivery_receipt.php" . ($is_edit_mode ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }
}

// 7. Pass everything to the view
$page_title = 'Delivery Receipt';
$view = 'views/delivery_receipt_form.php';
include 'templates/layout.php'; // passes $form_data and $view
?>
