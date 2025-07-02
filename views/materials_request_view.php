<?php
if (!isset($_GET['id'])) {
    header("Location: materials_request_admin.php");
    exit;
}

$request_id = intval($_GET['id']);
$request = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT r.*, u.username 
    FROM materials_requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = $request_id
"));

if (!$request) {
    echo "<div class='alert alert-danger'>Request not found</div>";
    exit;
}

$items = mysqli_query($conn, "
    SELECT * FROM materials_request_items
    WHERE request_id = $request_id
");
$stmt = $conn->prepare("SELECT r.*, u.username FROM materials_requests r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

?>

<div class="container-fluid">
    <h2 class="mb-4">Request Details #<?= $request['id'] ?></h2>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Requested By:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($request['username']) ?></dd>
                        
                        <dt class="col-sm-4">Request Date:</dt>
                        <dd class="col-sm-8"><?= date('M d, Y h:i A', strtotime($request['request_date'])) ?></dd>
                        
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= 
                                $request['status'] == 'approved' ? 'success' : 
                                ($request['status'] == 'denied' ? 'danger' : 'warning') 
                            ?>">
                                <?= ucfirst($request['status']) ?>
                            </span>
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
                    <dl class="row">
                        <dt class="col-sm-6">Non-Taxed Amount:</dt>
                        <dd class="col-sm-6">₱<?= number_format($request['non_taxed_price'], 2) ?></dd>
                        
                        <dt class="col-sm-6">Taxed Amount (12%):</dt>
                        <dd class="col-sm-6">₱<?= number_format($request['taxed_price'], 2) ?></dd>
                        
                        <dt class="col-sm-6">Grand Total:</dt>
                        <dd class="col-sm-6">₱<?= number_format($request['non_taxed_price'] + $request['taxed_price'], 2) ?></dd>
                    </dl>
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
                        <?php while ($item = mysqli_fetch_assoc($items)): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= htmlspecialchars($item['unit']) ?></td>
                            <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['taxable'] ? 'Yes' : 'No' ?></td>
                            <td>₱<?= number_format($item['subtotal'], 2) ?></td>
                            <td>₱<?= number_format($item['tax_amount'], 2) ?></td>
                            <td>₱<?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="materials_request_admin.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Requests
        </a>
    </div>
</div>