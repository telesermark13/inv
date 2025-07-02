<?php

// Ensure required variables exist or set defaults
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$requester_filter = $_GET['requester'] ?? 'all';
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Make sure you have run the queries that define $requesters and $requests before this file is loaded
?>

<?php
// Assume $status_filter, $date_filter, $requester_filter, $requesters, $requests, $_SESSION['csrf_token'] are all set
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Materials Request Approval</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-4">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Materials Request Approval</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status-filter">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="denied" <?= $status_filter === 'denied' ? 'selected' : '' ?>>Denied</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" id="date-filter" value="<?= htmlspecialchars($date_filter) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Requester</label>
                    <select class="form-select" id="requester-filter">
                        <option value="all" <?= $requester_filter === 'all' ? 'selected' : '' ?>>All Requesters</option>
                        <?php foreach ($requesters as $requester): ?>
                            <option value="<?= htmlspecialchars($requester['username']) ?>" <?= $requester_filter === $requester['username'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($requester['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="reset-filters" class="btn btn-secondary w-100">
                        <i class="fas fa-sync-alt"></i> Reset
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="requestsTable">
                    <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Requester</th>
                        <th>Date</th>
                        <th>Non-Taxed</th>
                        <th>Taxed (12%)</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>View Details</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['id']) ?></td>
                            <td><?= htmlspecialchars($request['requester_username']) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($request['request_date']))) ?></td>
                            <td>₱<?= number_format($request['total_amount_nontaxed'] ?? 0.00, 2) ?></td>
                            <td>₱<?= number_format($request['total_tax_amount'] ?? 0.00, 2) ?></td>
                            <td class="fw-bold">₱<?= number_format($request['grand_total_amount'] ?? 0.00, 2) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    ($request['status'] == 'approved') ? 'success' : (
                                        $request['status'] == 'denied' ? 'danger' : (
                                            $request['status'] == 'pending' ? 'warning text-dark' : 'secondary'
                                        )
                                    )
                                ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $request['status']))) ?>
                                </span>
                                <?php if ($request['status'] != 'pending' && !empty($request['processed_by_username'])): ?>
                                    <small class="d-block text-muted">
                                        by <?= htmlspecialchars($request['processed_by_username']) ?>
                                        on <?= htmlspecialchars(date('M d, Y', strtotime($request['processed_at']))) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-info view-details"
                                    data-bs-toggle="modal"
                                    data-bs-target="#requestDetailsModal"
                                    data-request-id="<?= $request['id'] ?>">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if ($request['status'] == 'pending'): ?>
                                    <form action="materials_request_approve.php" method="post" style="display:inline">
                                        <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form action="materials_request_approve.php" method="post" style="display:inline">
                                        <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="submit" name="action" value="deny" class="btn btn-danger btn-sm">Deny</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Request Details -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="requestDetailsModalLabel">Request Details #<span id="modalRequestId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <div class="text-center my-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p>Loading request details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#requestsTable').DataTable({ responsive: true });

    $('#status-filter, #date-filter, #requester-filter').on('change', function() {
        const status = $('#status-filter').val();
        const date = $('#date-filter').val();
        const requester = $('#requester-filter').val();
        const params = [];
        if (status && status !== "all") params.push("status=" + status);
        if (date) params.push("date=" + date);
        if (requester && requester !== "all") params.push("requester=" + requester);
        window.location = "materials_request_admin.php" + (params.length ? "?" + params.join("&") : "");
    });
    $('#reset-filters').on('click', function() {
        window.location = "materials_request_admin.php";
    });

    $('#requestDetailsModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const requestId = button.data('request-id');
        $('#modalRequestId').text(requestId);
        $('#requestDetailsContent').html('<div class="text-center my-5"><div class="spinner-border text-primary"></div><p>Loading request details...</p></div>');
        $.ajax({
            url: "ajax_get_request_details.php?id=" + requestId,
            method: "GET",
            success: function(data) {
                $('#requestDetailsContent').html(data);
            },
            error: function(xhr, status, error) {
                $('#requestDetailsContent').html('<div class="alert alert-danger">Error loading request details: ' + error + '</div>');
            }
        });
    });
});
</script>
</body>
</html>
