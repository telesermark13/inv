<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$request_id = intval($_GET['id']);

// Get request with admin info
$stmt = $conn->prepare("SELECT r.*, 
                       u.username AS requester_username,
                       a.username AS admin_username,
                       r.processed_at
                       FROM materials_requests r 
                       JOIN users u ON r.user_id = u.id
                       LEFT JOIN users a ON r.processed_by = a.id
                       WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    die("Request not found");
}

// Get request items

// Calculate totals manually
$items = $conn->query("
    SELECT mri.*, mi.name AS item_name, mi.sku AS master_sku
    FROM materials_request_items mri
    LEFT JOIN master_items mi ON mri.master_item_id = mi.id
    WHERE mri.request_id = $request_id
");

$item_rows = [];
$non_taxed_price = 0;
$taxed_price = 0;
while ($item = $items->fetch_assoc()) {
    $item_rows[] = $item;
    $subtotal = $item['price'] * $item['quantity'];
    $tax = !empty($item['taxable']) ? $subtotal * 0.12 : 0;
    if (!empty($item['taxable'])) {
        $taxed_price += $subtotal + $tax;
    } else {
        $non_taxed_price += $subtotal;
    }
}

$grand_total = $non_taxed_price + $taxed_price;
?>


<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Requested By:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($request['requester_username']) ?></dd>

                        <dt class="col-sm-4">Request Date:</dt>
                        <dd class="col-sm-8"><?= date('M d, Y h:i A', strtotime($request['request_date'])) ?></dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?=
                                                    $request['status'] == 'approved' ? 'success' : ($request['status'] == 'denied' ? 'danger' : 'warning')
                                                    ?>">
                                <?= ucfirst($request['status']) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Processed By:</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($request['admin_username'])): ?>
                                <?= htmlspecialchars($request['admin_username']) ?>
                                on <?= date('M d, Y h:i A', strtotime($request['processed_at'])) ?>
                            <?php else: ?>
                                <em>Pending</em>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Financial Summary</h5>
                </div>
                <div class="card-body">
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-6">Non-Taxed Amount:</dt>
                            <dd class="col-sm-6">₱<?= number_format($non_taxed_price, 2) ?></dd>

                            <dt class="col-sm-6">Taxed Amount (12%):</dt>
                            <dd class="col-sm-6">₱<?= number_format($taxed_price, 2) ?></dd>

                            <dt class="col-sm-6">Grand Total:</dt>
                            <dd class="col-sm-6">₱<?= number_format($grand_total, 2) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Requested Items</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>SKU</th>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Taxable</th>
                            <th>Subtotal</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($item_rows as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['sku'] ?? $item['master_sku'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['description']) ?></td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td><?= !empty($item['taxable']) ? 'Yes' : 'No' ?></td>
                                <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                <td>
                                    ₱<?= number_format(!empty($item['taxable']) ? $item['price'] * $item['quantity'] * 0.12 : 0, 2) ?>
                                </td>
                                <td>
                                    ₱<?= number_format($item['price'] * $item['quantity'] + (!empty($item['taxable']) ? $item['price'] * $item['quantity'] * 0.12 : 0), 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>