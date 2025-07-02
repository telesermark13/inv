<?php
global $orders, $status_filter, $search_term;

function displayOrDash($value)
{
  return ($value !== null && $value !== '') ? htmlspecialchars($value) : '<span class="text-muted">-</span>';
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex align-items-center" style="height: 60px;">
      <h3 class="card-title mb-0">Purchase Orders</h3>
    </div>
    <div class="card-body">

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($_SESSION['success']);
          unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($_SESSION['error']);
          unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form class="row mb-4 align-items-end" method="GET">
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Statuses</option>
            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved_to_order" <?= $status_filter == 'approved_to_order' ? 'selected' : '' ?>>Approved</option>
            <option value="ordered" <?= $status_filter == 'ordered' ? 'selected' : '' ?>>Ordered</option>
            <option value="partially_received" <?= $status_filter == 'partially_received' ? 'selected' : '' ?>>Partial Received</option>
            <option value="fully_received" <?= $status_filter == 'fully_received' ? 'selected' : '' ?>>Fully Received</option>
            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Search</label>
          <input type="text" class="form-control" name="search" placeholder="Search invoice, note, supplier" value="<?= htmlspecialchars($search_term ?? '') ?>">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary mt-3 w-100" type="submit">Filter</button>
        </div>
        <div class="col-md-2">
          <a href="purchase_orders.php" class="btn btn-secondary mt-3 w-100">Reset</a>
        </div>
      </form>

      <div class="table-responsive">
        <table id="purchaseOrdersTable" class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>PO #</th>
              <th>Status</th>
              <th>Supplier</th>
              <th>Invoice #</th>
              <th>Total (Est.)</th>
              <th>Created By</th>
              <th>Material Request</th>
              <th style="width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($orders && $orders->num_rows > 0): ?>
              <?php while ($row = $orders->fetch_assoc()): ?>
                <tr>
                  <td><?= displayOrDash($row['id']) ?></td>
                  <td>
                    <?php
                    $status = $row['status'] ?? '';
                    $badge = 'secondary';
                    switch (strtolower($status)) {
                      case 'pending':
                        $badge = 'warning';
                        break;
                      case 'approved_to_order':
                        $badge = 'info';
                        break;
                      case 'ordered':
                        $badge = 'primary';
                        break;
                      case 'partially_received':
                        $badge = 'dark';
                        break;
                      case 'fully_received':
                        $badge = 'success';
                        break;
                      case 'cancelled':
                        $badge = 'danger';
                        break;
                    }
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                  </td>
                  <td><?= displayOrDash($row['supplier_name'] ?? $row['supplier'] ?? '') ?></td>
                  <td><?= displayOrDash($row['sales_invoice_no'] ?? '') ?></td>
                  <td>
                    <?php
                    $total = isset($row['total_est']) ? $row['total_est'] : 0;
                    echo '<strong>₱' . number_format($total, 2) . '</strong>';
                    ?>
                  </td>

                  <td><?= displayOrDash($row['created_by_username'] ?? $row['created_by'] ?? '') ?></td>
                  <td><?= isset($row['material_request_id_display']) && $row['material_request_id_display'] ? 'MR-' . $row['material_request_id_display'] : '<span class="text-muted">-</span>' ?></td>
                  <td>
                    <button type="button"
                      class="btn btn-info btn-sm mb-1 w-100 view-po-btn"
                      data-po-id="<?= htmlspecialchars($row['id']) ?>">
                      <i class="fas fa-eye"></i> View
                    </button>
                    <button type="button"
                      class="btn btn-success btn-sm w-100 receive-po-btn"
                      data-po-id="<?= htmlspecialchars($row['id']) ?>">
                      <i class="fas fa-check-circle"></i> Receive
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">No purchase orders found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Receive PO Modal -->
<div class="modal fade" id="receivePOModal" tabindex="-1" aria-labelledby="receivePOModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="receivePOModalLabel">Receive Purchase Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="receivePOModalBody">
        <div class="text-center my-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-3">Loading PO details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PO Details Modal -->
<div class="modal fade" id="viewPOModal" tabindex="-1" aria-labelledby="viewPOModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="viewPOModalLabel">Purchase Order Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="viewPOModalBody">
        <div class="text-center my-5">
          <div class="spinner-border text-info" role="status"></div>
          <p class="mt-3">Loading details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    // ---- Main Table (Page Load, Safe to Init Once) ----
    $('#purchaseOrdersTable').DataTable({
      "order": [[0, "desc"]],
      "pageLength": 10,
      "responsive": true
    });

    // ---- Modal: Receive PO ----
    $(document).on('click', '.receive-po-btn', function() {
      var po_id = $(this).data('po-id');
      $('#receivePOModal').modal('show');
      $('#receivePOModalBody').html('<div class="text-center my-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading form...</p></div>');
      $.get('receive_po_modal_form.php', { po_id: po_id }, function(data) {
        $('#receivePOModalBody').html(data);

        // If the form loaded contains a table that should be a DataTable, re-init it safely
        if ($('#receive-po-items-table').length) {
          if ($.fn.DataTable.isDataTable('#receive-po-items-table')) {
            $('#receive-po-items-table').DataTable().destroy();
          }
          $('#receive-po-items-table').DataTable({
            "responsive": true
          });
        }
      }).fail(function() {
        $('#receivePOModalBody').html('<div class="alert alert-danger">Failed to load PO receipt form.</div>');
      });
    });

    // ---- Modal: View PO Details ----
    $(document).on('click', '.view-po-btn', function() {
      var po_id = $(this).data('po-id');
      $('#viewPOModal').modal('show');
      $('#viewPOModalBody').html('<div class="text-center my-5"><div class="spinner-border text-info"></div><p class="mt-3">Loading details...</p></div>');
      $.get('ajax_get_po_details.php', { po_id: po_id }, function(data) {
        $('#viewPOModalBody').html(data);

        // If details contains a table that should be a DataTable, re-init it safely
        if ($('#view-po-items-table').length) {
          if ($.fn.DataTable.isDataTable('#view-po-items-table')) {
            $('#view-po-items-table').DataTable().destroy();
          }
          $('#view-po-items-table').DataTable({
            "responsive": true
          });
        }
      }).fail(function() {
        $('#viewPOModalBody').html('<div class="alert alert-danger">Failed to load PO details.</div>');
      });
    });

    // ---- AJAX PO Receive Submission ----
    $(document).on('submit', '#receive-po-form', function(e) {
      e.preventDefault();
      let $form = $(this);
      let formData = $form.serialize();
      let $resultDiv = $('#receive-po-result');
      $form.find('button[type=submit]').prop('disabled', true);

      $.post('process_po_receipt.php', formData, function(response) {
        if (typeof response === 'string' && response.indexOf('successfully') !== -1) {
          $resultDiv.html('<div class="alert alert-success">Goods received and inventory updated!<br>Reloading...</div>');
          setTimeout(() => location.reload(), 1500);
        } else if (typeof response === 'string' && response.indexOf('error') !== -1) {
          $resultDiv.html('<div class="alert alert-danger">' + response + '</div>');
        } else {
          $resultDiv.html('<div class="alert alert-info">' + response + '</div>');
        }
      })
      .fail(function(xhr) {
        $resultDiv.html('<div class="alert alert-danger">Failed to process receipt.<br>' + xhr.responseText + '</div>');
      })
      .always(function() {
        $form.find('button[type=submit]').prop('disabled', false);
      });
    });

  });
</script>
