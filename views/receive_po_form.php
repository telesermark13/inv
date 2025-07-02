<?php
global $po_header, $po_items, $po_id; // From controller
?>
<div class="mb-3">
    <label for="sales_invoice_no" class="form-label">Sales Invoice #</label>
    <input type="text" name="sales_invoice_no" id="sales_invoice_no"
        class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>" placeholder="Sales Invoice #" required>
</div>
<div class="card-body">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php unset($_SESSION['error']);
    endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php unset($_SESSION['success']);
    endif; ?>

    <div class="mb-3">
        <p><strong>Supplier:</strong> <?= htmlspecialchars($po_header['supplier_name']) ?></p>
        <p><strong>Order Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($po_header['order_date']))) ?></p>
        <p><strong>Current Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $po_header['status']))) ?></p>
    </div>

    <form action="process_po_receipt.php" method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="po_id" value="<?= $po_header['id'] ?>">

        <div class="mb-3 row">
            <div class="col-md-4">
                <label for="delivery_receipt_no" class="form-label">Supplier Delivery Receipt # (Optional)</label>
                <input type="text" class="form-control" id="delivery_receipt_no" name="supplier_dr_no">
            </div>
            <div class="col-md-4">
                <label for="received_date" class="form-label">Date Received <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="received_date" name="received_date" value="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback">Please select the date received.</div>
            </div>
            <div class="mb-3">
                <label for="sales_invoice_no" class="form-label">Sales Invoice #</label>
                <input type="text" name="sales_invoice_no" id="sales_invoice_no"
                    class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>" placeholder="Sales Invoice #" required>
            </div>

            <div class="col-md-4">
                <label for="received_by_user" class="form-label">Received By (User) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="received_by_user" name="received_by_user_name" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly required>
                <input type="hidden" name="received_by_user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
                <div class="invalid-feedback">Receiver name is required.</div>
            </div>
        </div>
        <div class="mb-3">
            <label for="receiving_notes" class="form-label">Receiving Notes (Optional)</label>
            <textarea class="form-control" id="receiving_notes" name="receiving_notes" rows="2"></textarea>
        </div>


        <h5 class="mt-4 mb-3">Items to Receive</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Item Description (SKU)</th>
                        <th>Unit</th>
                        <th class="text-end">Ordered</th>
                        <th class="text-end">Already Received</th>
                        <th class="text-end">Outstanding</th>
                        <th style="width: 15%;" class="text-end">Quantity Receiving Now <span class="text-danger">*</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($po_items as $index => $item): ?>
                        <?php
                        $ordered_qty = (float)$item['quantity'];
                        $received_qty_val = (float)$item['quantity_received_val'];
                        $outstanding_qty = $ordered_qty - $received_qty_val;
                        ?>
                        <?php if ($outstanding_qty > 0): // Only show items that still need receiving 
                        ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['description']) ?>
                                    <?php if ($item['sku']): ?>
                                        <small class="text-muted d-block">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                    <?php endif; ?>
                                    <input type="hidden" name="items[<?= $index ?>][po_item_id]" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="items[<?= $index ?>][master_item_id]" value="<?= $item['item_id'] ?>"> <input type="hidden" name="items[<?= $index ?>][master_item_type]" value="<?= $item['item_type'] ?>">
                                    <input type="hidden" name="items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?>">
                                </td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                <td class="text-end"><?= number_format($ordered_qty, 2) ?></td>
                                <td class="text-end"><?= number_format($received_qty_val, 2) ?></td>
                                <td class="text-end fw-bold"><?= number_format($outstanding_qty, 2) ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm text-end qty-receiving"
                                        name="items[<?= $index ?>][qty_receiving_now]"
                                        min="0"
                                        max="<?= $outstanding_qty ?>"
                                        step="any" value="0" required>
                                    <div class="invalid-feedback">Must be 0 to <?= $outstanding_qty ?>.</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (empty(array_filter($po_items, function ($i) {
                        return ((float)$i['quantity'] - (float)$i['quantity_received_val']) > 0;
                    }))): ?>
                        <tr>
                            <td colspan="6" class="text-center alert alert-info">All items for this PO have been fully received.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty(array_filter($po_items, function ($i) {
            return ((float)$i['quantity'] - (float)$i['quantity_received_val']) > 0;
        }))): ?>
            <div class="mt-4 d-flex justify-content-end">
                <a href="purchase_orders.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Process Receipt & Update Stock</button>
            </div>
        <?php endif; ?>
    </form>
</div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Bootstrap form validation
        const form = document.querySelector('.needs-validation');
        if (form) {
            form.addEventListener('submit', function(event) {
                let allValid = true;
                document.querySelectorAll('.qty-receiving').forEach(input => {
                    const qtyReceiving = parseFloat(input.value) || 0;
                    const maxQty = parseFloat(input.getAttribute('max')) || 0;
                    if (qtyReceiving < 0 || qtyReceiving > maxQty) {
                        input.classList.add('is-invalid');
                        input.classList.remove('is-valid');
                        allValid = false;
                    } else if (input.hasAttribute('required') && qtyReceiving === 0 && maxQty > 0 && /* check if any other field is filled */ true) {
                        // Allow 0 if user intends to receive nothing this time for this item,
                        // but overall form validity depends on other required fields.
                        // The `required` attribute on the input itself will handle if it MUST be > 0.
                        // For now, a value of 0 is considered valid for submission to backend logic.
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    }
                });


                if (!form.checkValidity() || !allValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }
    });
</script>