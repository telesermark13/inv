<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    exit('Access denied');
}

// Fetch all tools
$tools = [];
$sql = "SELECT t.*, u.username AS assigned_person_name
        FROM tools t
        LEFT JOIN users u ON t.assigned_person = u.id
        ORDER BY t.name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tools[] = $row;
    }
}

// Prepare user options for the select in the modal and filter
$user_res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
$filter_users = [];
while($u = $user_res->fetch_assoc()) {
    $filter_users[] = $u;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tools | Inventory System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        body { background: #f9fafb; }
        .table thead th { background: #f1f3f6; }
        .page-title { display: flex; align-items: center; gap: 0.6rem; }
        .btn { min-width: 80px; }
        .datatable-custom-filters input, .datatable-custom-filters select { margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="page-title h3 mb-0">
            <i class="bi bi-wrench-adjustable"></i> Tools
        </span>
        <button class="btn btn-success" id="openAddToolModal">
            <i class="bi bi-plus-circle"></i> Add Tool
        </button>
    </div>

    <!-- Custom filters -->
    <div class="row datatable-custom-filters mb-2">
      <div class="col-md-3">
        <input id="filterName" type="text" class="form-control" placeholder="Filter by Name">
      </div>
      <div class="col-md-2">
        <input id="filterSize" type="text" class="form-control" placeholder="Filter by Size">
      </div>
      <div class="col-md-3">
        <input id="filterDesc" type="text" class="form-control" placeholder="Filter by Description">
      </div>
      <div class="col-md-4">
        <select id="filterAssignedPerson" class="form-select">
          <option value="">Filter by Assigned Person</option>
          <?php foreach($filter_users as $fu): ?>
            <option><?= htmlspecialchars($fu['username']) ?></option>
          <?php endforeach; ?>
          <option>—</option> <!-- For unassigned -->
        </select>
      </div>
    </div>

    <div class="card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <table id="toolsTable" class="table table-bordered table-striped m-0 align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Size</th>
                    <th>Description</th>
                    <th>Assigned Person</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($tools)): ?>
                    <?php foreach ($tools as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= (int)$row['quantity'] ?></td>
                            <td><?= htmlspecialchars($row['size']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= htmlspecialchars($row['assigned_person_name'] ?? '—') ?></td>
                            <td>
                                <?php
                                $badge = [
                                    'good' => 'success',
                                    'damaged' => 'danger',
                                    'under repair' => 'warning',
                                    'missing' => 'secondary',
                                    'retired' => 'dark'
                                ][$row['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucwords($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="tool_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="tool_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this tool?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No tools found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Add Tool Modal -->
<div class="modal fade" id="addToolModal" tabindex="-1" aria-labelledby="addToolModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content needs-validation" id="addToolForm" method="post" autocomplete="off" novalidate>
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addToolModalLabel">Add Tool</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="tool_name" class="form-label">Tool Name</label>
            <input type="text" class="form-control" name="name" id="tool_name" required>
          </div>
          <div class="col-md-3">
            <label for="tool_qty" class="form-label">Quantity</label>
            <input type="number" class="form-control" name="quantity" id="tool_qty" min="0" value="1" required>
          </div>
          <div class="col-md-3">
            <label for="tool_size" class="form-label">Size</label>
            <input type="text" class="form-control" name="size" id="tool_size" required>
          </div>
        </div>
        <div class="mb-3 mt-3">
          <label for="tool_description" class="form-label">Description</label>
          <textarea class="form-control" name="description" id="tool_description" rows="2"></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="assigned_person" class="form-label">Assigned Person</label>
            <select class="form-select" name="assigned_person" id="assigned_person">
              <option value="">— None —</option>
              <?php
              // Re-query to reset result pointer if above was used
              $user_res2 = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
              while($u = $user_res2->fetch_assoc()): ?>
                  <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label for="tool_status" class="form-label">Status</label>
            <select class="form-select" name="status" id="tool_status" required>
              <option value="good">Good</option>
              <option value="damaged">Damaged</option>
              <option value="under repair">Under Repair</option>
              <option value="missing">Missing</option>
              <option value="retired">Retired</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Add Tool</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<!-- JS: jQuery, Bootstrap, DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    // DataTables with 10 per page
    var table = $('#toolsTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [10, 25, 50, 100],
        "language": { "search": "Filter:" }
    });

    // Per-column filters (Name, Size, Description, Assigned Person)
    $('#filterName').on('keyup change', function () {
        table.column(0).search(this.value).draw();
    });
    $('#filterSize').on('keyup change', function () {
        table.column(2).search(this.value).draw();
    });
    $('#filterDesc').on('keyup change', function () {
        table.column(3).search(this.value).draw();
    });
    $('#filterAssignedPerson').on('change', function () {
        let val = this.value;
        // Special for "—" (unassigned)
        if (val === '—') {
            table.column(4).search('^—$', true, false).draw();
        } else {
            table.column(4).search(val).draw();
        }
    });

    // Open modal on button click
    $('#openAddToolModal').click(function() {
        $('#addToolModal').modal('show');
    });

    // Bootstrap validation
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
      form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // AJAX submit for Add Tool modal
    $('#addToolForm').on('submit', function(e){
        e.preventDefault();
        if (!this.checkValidity()) return;
        var formData = $(this).serialize();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.post('tool_add.php', formData, function(response){
            if(response.trim() === 'success'){
                $('#addToolModal').modal('hide');
                location.reload(); // Reload the page to update the table
            }else{
                alert("Failed to add tool: " + response);
            }
        }).always(function(){
            $btn.prop('disabled', false);
        });
    });
});
</script>
</body>
</html>
