<?php
global $conn;

// Fetch suppliers for dropdown
$suppliers_list = [];
if ($conn) {
  $suppliers_result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
  if ($suppliers_result) {
    while ($supplier = $suppliers_result->fetch_assoc()) {
      $suppliers_list[] = $supplier;
    }
  }
}

// Fetch master items for dropdown
$master_items = [];
if ($conn) {
  $items_result = $conn->query("SELECT * FROM master_items ORDER BY name ASC");
  if ($items_result) {
    while ($item = $items_result->fetch_assoc()) {
      $master_items[] = $item;
    }
  }
}

// For JS template for new rows
$js_item_options = "";
foreach ($master_items as $item) {
  $js_item_options .= "<option value='" . htmlspecialchars($item['id']) . "' "
    . "data-name='" . htmlspecialchars($item['name']) . "' "
    . "data-category='" . htmlspecialchars($item['category']) . "' "
    . "data-sku='" . htmlspecialchars($item['sku']) . "' "
    . "data-desc='" . htmlspecialchars($item['description']) . "' "
    . "data-unit='" . htmlspecialchars($item['unit']) . "' "
    . "data-unit_price='" . htmlspecialchars($item['unit_price']) . "'>"
    . htmlspecialchars($item['name']) . "</option>";
}
?>

<div class="card shadow">
  <div class="card-header bg-primary text-white">
    <h3 class="card-title mb-0">Materials Request Form</h3>
  </div>
  <div class="card-body">
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['warning'])): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['warning']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <form method="POST" action="materials_request_save.php" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="supplier_id" class="form-label">Supplier (Optional)</label>
          <select class="form-select" id="supplier_id" name="supplier_id">
            <option value="">-- Select Supplier --</option>
            <?php foreach ($suppliers_list as $supplier): ?>
              <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="request_date" class="form-label">Request Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" id="request_date" name="request_date" value="<?= date('Y-m-d') ?>" required>
          <div class="invalid-feedback">Please select a date.</div>
        </div>
        <div class="col-md-3">
          <label for="tax_rate" class="form-label">Tax Rate <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="12" step="0.01" min="0" required>
            <span class="input-group-text">%</span>
          </div>
          <div class="invalid-feedback">Please enter a tax rate.</div>
        </div>
      </div>

      <h5 class="mb-3">Items Requested</h5>
      <div class="table-responsive">
        <table class="table table-bordered align-middle" id="requestItemsTable">
          <thead class="table-light">
            <tr>
              <th>Item Name</th>
              <th>Serial No.</th>
              <th>Description</th>
              <th>Unit</th>
              <th>Category</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Taxable</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <select class="form-select item-name-select" name="items[0][item_id]" required>
                  <option value="">Select item</option>
                  <?php foreach ($master_items as $item): ?>
                    <option
                      value="<?= htmlspecialchars($item['id']) ?>"
                      data-name="<?= htmlspecialchars($item['name']) ?>"
                      data-category="<?= htmlspecialchars($item['category']) ?>"
                      data-sku="<?= htmlspecialchars($item['sku']) ?>"
                      data-desc="<?= htmlspecialchars($item['description']) ?>"
                      data-unit="<?= htmlspecialchars($item['unit']) ?>"
                      data-unit_price="<?= htmlspecialchars($item['unit_price']) ?>">
                      <?= htmlspecialchars($item['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" class="name-input" name="items[0][name]">
              </td>
              <td><input type="text" class="form-control sku-input" name="items[0][sku]" readonly></td>
              <td><input type="text" class="form-control desc-input" name="items[0][description]" readonly></td>
              <td><input type="text" class="form-control unit-input" name="items[0][unit]" readonly></td>
              <td><input type="text" class="form-control category-input" name="items[0][category]" readonly></td>
              <td><input type="number" class="form-control qty-input" name="items[0][qty]" min="0" step="any"></td>
              <td><input type="number" class="form-control price-input" name="items[0][price]" min="0" step="any"></td>
              <td class="text-center"><input type="checkbox" class="taxable-input" name="items[0][taxable]" value="1" checked></td>
              <td><input type="text" class="form-control total-input" readonly></td>
              <td><button type="button" class="btn btn-danger btn-sm remove-row" style="display:none;">&times;</button></td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6" class="text-end fw-bold">Subtotal (Non-Taxed):</td>
              <td>
                <div class="input-group"><span class="input-group-text">₱</span><input type="text" class="form-control" id="subtotal_nontaxed_total" readonly></div>
              </td>
              <td></td>
            </tr>
            <tr>
              <td colspan="6" class="text-end fw-bold">Total Tax:</td>
              <td>
                <div class="input-group"><span class="input-group-text">₱</span><input type="text" class="form-control" id="tax_total_amount" readonly></div>
              </td>
              <td></td>
            </tr>
            <tr>
              <td colspan="6" class="text-end fw-bold fs-5">Grand Total:</td>
              <td>
                <div class="input-group"><span class="input-group-text fw-bold fs-5">₱</span><input type="text" class="form-control fs-5 fw-bold" id="grand_total_display" readonly></div>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
        <button type="button" class="btn btn-success" id="addMoreRow"><i class="bi bi-plus-circle"></i> Add Row</button>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="reset" class="btn btn-secondary me-2">
          <i class="bi bi-x-circle"></i> Reset
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save"></i> Submit Request
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    function addAutofillListeners(row, idx) {
      // On item select, autofill all relevant fields
      row.querySelector('.item-name-select').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        row.querySelector('.sku-input').value = selected.getAttribute('data-sku') || '';
        row.querySelector('.desc-input').value = selected.getAttribute('data-desc') || '';
        row.querySelector('.unit-input').value = selected.getAttribute('data-unit') || '';
        row.querySelector('.price-input').value = selected.getAttribute('data-unit_price') || '';
        row.querySelector('.category-input').value = selected.getAttribute('data-category') || '';
        row.querySelector('.name-input').value = selected.getAttribute('data-name') || '';
        calculateTotals();
      });


      // Remove row
      const removeBtn = row.querySelector('.remove-row');
      if (removeBtn) {
        removeBtn.style.display = '';
        removeBtn.addEventListener('click', function() {
          row.remove();
          calculateTotals();
        });
      }

      // Calculation on input/change for qty, price, taxable
      row.querySelector('.qty-input').addEventListener('input', calculateTotals);
      row.querySelector('.price-input').addEventListener('input', calculateTotals);
      row.querySelector('.taxable-input').addEventListener('change', calculateTotals);
    }

    // Add autofill to initial row if present
    const firstRow = document.querySelector('#requestItemsTable tbody tr');
    if (firstRow) addAutofillListeners(firstRow, 0);

    // Add new rows
    document.getElementById('addMoreRow').addEventListener('click', function() {
      const tbody = document.querySelector('#requestItemsTable tbody');
      const rowCount = tbody.querySelectorAll('tr').length;
      const jsOptions = `<?= addslashes($js_item_options) ?>`;

      const newRow = document.createElement('tr');
      newRow.innerHTML = `
      <td>
        <select class="form-select item-name-select" name="items[${rowCount}][item_id]" required>
          <option value="">Select item</option>${jsOptions}
        </select>
        <input type="hidden" class="name-input" name="items[${rowCount}][name]">
      </td>
      <td><input type="text" class="form-control sku-input" name="items[${rowCount}][sku]" readonly></td>
      <td><input type="text" class="form-control desc-input" name="items[${rowCount}][description]" readonly></td>
      <td><input type="text" class="form-control unit-input" name="items[${rowCount}][unit]" readonly></td>
      <td><input type="text" class="form-control category-input" name="items[${rowCount}][category]" readonly></td>
      <td><input type="number" class="form-control qty-input" name="items[${rowCount}][qty]" min="0" step="any"></td>
      <td><input type="number" class="form-control price-input" name="items[${rowCount}][price]" min="0" step="any"></td>
      <td class="text-center"><input type="checkbox" class="taxable-input" name="items[${rowCount}][taxable]" value="1" checked></td>
      <td><input type="text" class="form-control total-input" readonly></td>
      <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
    `;
      tbody.appendChild(newRow);
      addAutofillListeners(newRow, rowCount);
      calculateTotals();
    });

    // Calculation for all rows
    function calculateTotals() {
      let subtotal = 0;
      let taxTotal = 0;
      let grandTotal = 0;
      const taxRate = parseFloat(document.getElementById('tax_rate').value) / 100 || 0;

      document.querySelectorAll('#requestItemsTable tbody tr').forEach(function(row) {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const isTaxable = row.querySelector('.taxable-input')?.checked;
        const lineSubtotal = qty * price;
        const lineTax = isTaxable ? lineSubtotal * taxRate : 0;
        const lineTotal = lineSubtotal + lineTax;
        row.querySelector('.total-input').value = lineTotal > 0 ? lineTotal.toFixed(2) : '';
        subtotal += lineSubtotal;
        taxTotal += lineTax;
        grandTotal += lineTotal;
      });

      document.getElementById('subtotal_nontaxed_total').value = subtotal.toFixed(2);
      document.getElementById('tax_total_amount').value = taxTotal.toFixed(2);
      document.getElementById('grand_total_display').value = grandTotal.toFixed(2);
    }

    // Initial calculation
    calculateTotals();

    // Bootstrap form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
</script>