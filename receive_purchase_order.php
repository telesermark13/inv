<?php
// receive_purchase_order.php (Controller)
require_once __DIR__ . '/includes/auth.php';
// require_once __DIR__ . '/includes/is_admin.php'; // Or purchasing role
require_once __DIR__ . '/includes/db.php';

if (!isset($_GET['po_id']) || !filter_var($_GET['po_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Invalid Purchase Order ID.";
    header("Location: purchase_orders.php");
    exit;
}
$po_id = (int)$_GET['po_id'];

// Fetch PO Header
$stmt_po = $conn->prepare(
    "SELECT po.*, s.name as supplier_name
     FROM purchase_orders po
     JOIN suppliers s ON po.supplier_id = s.id
     WHERE po.id = ? AND po.status IN ('approved_to_order', 'ordered', 'partially_received')" // Only allow receiving for these statuses
);
if (!$stmt_po) die("Prepare PO select failed: " . $conn->error);
$stmt_po->bind_param("i", $po_id);
if (!$stmt_po->execute()) die("Execute PO select failed: " . $stmt_po->error);
$po_header_result = $stmt_po->get_result();
$po_header = $po_header_result->fetch_assoc();
$stmt_po->close();

if (!$po_header) {
    $_SESSION['error'] = "Purchase Order #{$po_id} not found or not in a receivable state.";
    header("Location: purchase_orders.php");
    exit;
}

// Fetch PO Items with details on already received quantities
// We need a way to track received quantity per PO item. Let's assume `purchase_order_items` gets a `quantity_received` column.
// ALTER TABLE `purchase_order_items` ADD COLUMN `quantity_received` DECIMAL(10,2) DEFAULT 0.00 AFTER `unit_price`;
$stmt_items = $conn->prepare(
    "SELECT poi.*, COALESCE(poi.quantity_received, 0) as quantity_received_val
     FROM purchase_order_items poi
     WHERE poi.order_id = ? ORDER BY poi.id ASC"
);
// If linking to master items:
// SELECT poi.*, COALESCE(poi.quantity_received, 0) as quantity_received_val, i.name as master_item_name, i.sku as master_item_sku
// FROM purchase_order_items poi
// LEFT JOIN items i ON poi.item_id = i.id AND poi.item_type = 'current'
// LEFT JOIN old_stocks os ON poi.item_id = os.id AND poi.item_type = 'old'
// WHERE poi.order_id = ? ORDER BY poi.id ASC

if (!$stmt_items) die("Prepare PO items select failed: " . $conn->error);
$stmt_items->bind_param("i", $po_id);
if (!$stmt_items->execute()) die("Execute PO items select failed: " . $stmt_items->error);
$po_items_result = $stmt_items->get_result();
$po_items = [];
while($row = $po_items_result->fetch_assoc()) {
    $po_items[] = $row;
}
$stmt_items->close();


$page_title = "Receive Goods for PO-" . str_pad($po_id, 5, '0', STR_PAD_LEFT);
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); // Ensure CSRF token
$view = 'views/receive_po_form.php';
include 'templates/layout.php';
?>