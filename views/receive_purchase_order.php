<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Receive Goods for PO-<?= str_pad($po['id'], 5, '0', STR_PAD_LEFT) ?></h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['warning']) ?></div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>

            <form method="POST" action="process_po_receipt.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="po_id" value="<?= $po['id'] ?>">

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">PO Number</label>
                        <input type="text" class="form-control" value="PO-<?= str_pad($po['id'], 5, '0', STR_PAD_LEFT) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($po['supplier_name']) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="received_date" class="form-label">Received Date *</label>
                        <input type="datetime-local" class="form-control" id="received_date" name="received_date" 
                               value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="supplier_dr_no" class="form-label">Supplier DR Number</label>
                        <input type="text" class="form-control" id="supplier_dr_no" name="supplier_dr_no">
                    </div>
                    <div class="col-md-6">
                        <label for="receiving_notes" class="form-label">Receiving Notes</label>
                        <textarea class="form-control" id="receiving_notes" name="receiving_notes" rows="2"></textarea>
                    </div>
                </div>

                <h5 class="mb-3">Items to Receive</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>SKU</th>
                                <th>Ordered</th>
                                <th>Already Received</th>
                                <th>Outstanding</th>
                                <th>Receiving Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items->fetch_assoc()): 
                                $outstanding = $item['quantity'] - $item['quantity_received'];
                                if ($outstanding <= 0) continue; // Skip fully received items
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= htmlspecialchars($item['item_sku']) ?></td>
                                    <td><?= $item['quantity'] ?> <?= htmlspecialchars($item['item_unit']) ?></td>
                                    <td><?= $item['quantity_received'] ?> <?= htmlspecialchars($item['item_unit']) ?></td>
                                    <td><?= $outstanding ?> <?= htmlspecialchars($item['item_unit']) ?></td>
                                    <td>
                                        <input type="hidden" name="items[<?= $item['id'] ?>][po_item_id]" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="items[<?= $item['id'] ?>][master_item_id]" value="<?= $item['item_id'] ?>">
                                        <input type="hidden" name="items[<?= $item['id'] ?>][master_item_type]" value="<?= $item['item_type'] ?>">
                                        <input type="number" class="form-control" 
                                               name="items[<?= $item['id'] ?>][qty_receiving_now]" 
                                               min="0" max="<?= $outstanding ?>" step="1"
                                               value="<?= $outstanding ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="purchase_orders.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Receive Goods</button>
                </div>
            </form>
        </div>
    </div>
</div>