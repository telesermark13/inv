<?php
// views/delivery_history_view.php

// Assume $deliveries and $requesters are set in your controller, and you have: $status_filter, $date_filter, $requester_filter
// For table loop, we use $deliveries (mysqli_result) and $row
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Delivery History</h3>
        </div>

        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- FILTERS -->
            <form class="row mb-4" method="GET" action="">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Deliveries</option>
                        <option value="outstanding" <?= $status_filter === 'outstanding' ? 'selected' : '' ?>>With Outstanding</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prepared By</label>
                    <select class="form-select" name="prepared_by">
                        <option value="all" <?= $requester_filter === 'all' ? 'selected' : '' ?>>All Users</option>
                        <?php if ($requesters) while ($requester = mysqli_fetch_assoc($requesters)): ?>
                            <option value="<?= htmlspecialchars($requester['username']) ?>" <?= $requester_filter === $requester['username'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($requester['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" type="submit">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
            <!-- END FILTERS -->

            <?php if (mysqli_num_rows($deliveries) === 0): ?>
                <div class="alert alert-info">
                    No deliveries found matching your filters.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="deliveriesTable">
                        <thead class="table-light">
                            <tr>
                                <th>DR #</th>
                                <th>Client</th>
                                <th>Delivered By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total Qty</th>
                                <th>Total Amount</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $deliveries->fetch_assoc()): ?>
                                <?php
                                // Status logic
                                if ($row['outstanding'] == 0) {
                                    $status = 'Completed';
                                    $badge = 'success';
                                } else {
                                    $status = 'Outstanding';
                                    $badge = 'warning';
                                }
                                // Total Amount calculation
                                $total_amount = 0;
                                $total_amount_query = $conn->prepare("SELECT SUM(total_nontaxed) as total_amount FROM delivered_items WHERE delivery_number = ?");
                                $total_amount_query->bind_param("i", $row['delivery_number']);
                                $total_amount_query->execute();
                                $total_amount_result = $total_amount_query->get_result()->fetch_assoc();
                                $total_amount = $total_amount_result['total_amount'] ?? 0;
                                $total_amount_query->close();
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['delivery_number']) ?></td>
                                    <td><?= htmlspecialchars($row['client']) ?></td>
                                    <td><?= htmlspecialchars($row['prepared_by_name']) ?></td>
                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['date']))) ?></td>
                                    <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
                                    <td><?= number_format($row['total_quantity'] ?? 0, 2) ?></td>
                                    <td><?= number_format($total_amount, 2) ?></td>
                                    <td><?= htmlspecialchars($row['comments']) ?></td>
                                    <td>
                                        <a href="delivery_receipt_print.php?id=<?= $row['delivery_number'] ?>&copy=company"
                                            target="_blank"
                                            class="btn btn-sm btn-primary mb-1">
                                            <i class="bi bi-printer"></i> View/Print
                                        </a>
                                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                            data-bs-target="#editCommentModal" data-drid="<?= $row['delivery_number'] ?>">
                                            Add/Edit Comment
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for adding/editing comments -->
<div class="modal fade" id="editCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="save_delivery_comment.php">
            <input type="hidden" name="delivery_number" id="modal_dr_number">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Comment</h5>
                </div>
                <div class="modal-body">
                    <textarea name="comments" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    var editModal = document.getElementById('editCommentModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var drid = button.getAttribute('data-drid');
        document.getElementById('modal_dr_number').value = drid;
    });
</script>