<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
if ($po_id <= 0) {
    echo '<div class="alert alert-danger">Invalid PO ID.</div>';
    exit;
}

// Fetch the PO header
$stmt = $conn->prepare("SELECT po.*, u.username AS created_by_username, s.name AS supplier_name
                        FROM purchase_orders po
                        LEFT JOIN users u ON u.id = po.created_by
                        LEFT JOIN suppliers s ON s.id = po.supplier_id
                        WHERE po.id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_header = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po_header) {
    echo '<div class="alert alert-danger">Purchase Order not found.</div>';
    exit;
}

// Fetch PO items
$stmt = $conn->prepare("SELECT poi.*, i.name AS item_name, i.unit, i.sku
                        FROM purchase_order_items poi
                        LEFT JOIN items i ON i.id = poi.item_id
                        WHERE poi.order_id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_items = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $po_items[] = $row;
$stmt->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
?>

<form id="receive-po-form" action="process_po_receipt.php" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="po_id" value="<?= htmlspecialchars($po_header['id']) ?>">
    <div class="mb-3">
        <label for="sales_invoice_no" class="form-label">Sales Invoice #</label>
        <input type="text" name="sales_invoice_no" id="sales_invoice_no"
               class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>"
               placeholder="Sales Invoice #" required>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Supplier</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($po_header['supplier_name']) ?>" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Order Date</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($po_header['order_date']) ?>" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', $po_header['status'])) ?>" readonly>
        </div>
    </div>
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Ordered</th>
                    <th>Already Received</th>
                    <th>Outstanding</th>
                    <th>Qty Receiving Now</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po_items as $index => $item):
                    $ordered_qty = (float)$item['quantity'];
                    $received_qty = (float)$item['quantity_received'];
                    $outstanding_qty = $ordered_qty - $received_qty;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= htmlspecialchars($item['sku']) ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td><?= number_format($ordered_qty, 2) ?></td>
                    <td><?= number_format($received_qty, 2) ?></td>
                    <td><?= number_format($outstanding_qty, 2) ?></td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-end"
                               name="items[<?= $index ?>][qty_receiving_now]"
                               min="0" max="<?= $outstanding_qty ?>"
                               step="any" value="0"
                               <?= $outstanding_qty <= 0 ? 'readonly' : '' ?>>
                        <input type="hidden" name="items[<?= $index ?>][po_item_id]" value="<?= $item['id'] ?>">
                        <input type="hidden" name="items[<?= $index ?>][master_item_id]" value="<?= $item['item_id'] ?>">
                        <input type="hidden" name="items[<?= $index ?>][master_item_type]" value="current">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Received By (User)</label>
            <input type="text" class="form-control" name="received_by_user_name" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly>
            <input type="hidden" name="received_by_user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Date Received</label>
            <input type="date" class="form-control" name="received_date" value="<?= date('Y-m-d') ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Receiving Notes (Optional)</label>
        <textarea class="form-control" name="receiving_notes" rows="2"></textarea>
    </div>
    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Process Receipt &amp; Update Stock</button>
    </div>
</form>
<div id="receive-po-result"></div>
<script>
$(function(){
    $('.needs-validation').on('submit', function(event){
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>
