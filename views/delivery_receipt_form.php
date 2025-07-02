<?php
$is_edit = $form_data['is_edit'];
$delivery_number = $form_data['delivery_number'];
$existing_data = $form_data['existing_data'];
$existing_items_details = $form_data['existing_items_details'];
$previous_clients = $form_data['previous_clients'];
$previous_projects = $form_data['previous_projects'];
$previous_locations = $form_data['previous_locations'];
$all_inventory_items = $form_data['all_inventory_items'];
?>

<div class="card shadow">
  <div class="card-header bg-primary text-white">
    <h3 class="card-title mb-0"><?= $is_edit ? 'Edit' : 'Create' ?> Delivery Receipt</h3>
  </div>
  <div class="card-body">
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php unset($_SESSION['error']);
    endif; ?>
    <?php if (isset($_SESSION['warning'])): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['warning']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php unset($_SESSION['warning']);
    endif; ?>


    <form method="POST" action="delivery_receipt.php<?= $is_edit ? '?edit=' . $delivery_number : '' ?>" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="is_edit" value="<?= $is_edit ? '1' : '0' ?>">
      <input type="hidden" name="delivery_number_hidden" value="<?= htmlspecialchars($delivery_number) ?>">

      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label">Delivery Number</label>
          <input type="text" class="form-control" value="DR-<?= htmlspecialchars($delivery_number) ?>" readonly>
        </div>
        <div class="col-md-3">
          <label class="form-label">Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="date"
            value="<?= htmlspecialchars($is_edit && $existing_data ? date('Y-m-d', strtotime($existing_data['date'])) : date('Y-m-d')) ?>" required>
          <div class="invalid-feedback">Please select a date.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Client <span class="text-danger">*</span></label>
          <input type="text" name="client" class="form-control" list="client-list"
            value="<?= htmlspecialchars($is_edit && $existing_data ? $existing_data['client'] : '') ?>" required>
          <datalist id="client-list">
            <?php foreach ($previous_clients as $client_name): ?>
              <option value="<?= htmlspecialchars($client_name) ?>">
              <?php endforeach; ?>
          </datalist>
          <div class="invalid-feedback">Please enter client name.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Received By (Contact Person)</label>
          <input type="text" name="received_by" class="form-control"
            value="<?= htmlspecialchars($is_edit && $existing_data ? $existing_data['received_by'] : '') ?>">
          <!-- No invalid-feedback needed for optional fields -->
        </div>
      </div>
      <div class="row mb-4">
        <div class="col-md-6">
          <label class="form-label">Project <span class="text-danger">*</span></label>
          <input type="text" name="project" class="form-control" list="project-list"
            value="<?= htmlspecialchars($is_edit && $existing_data ? $existing_data['project'] : '') ?>" required>
          <datalist id="project-list">
            <?php foreach ($previous_projects as $project_name): ?>
              <option value="<?= htmlspecialchars($project_name) ?>">
              <?php endforeach; ?>
          </datalist>
          <div class="invalid-feedback">Please enter project name.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Location <span class="text-danger">*</span></label>
          <input type="text" name="location" class="form-control" list="location-list"
            value="<?= htmlspecialchars($is_edit && $existing_data ? $existing_data['location'] : '') ?>" required>
          <datalist id="location-list">
            <?php foreach ($previous_locations as $location_name): ?>
              <option value="<?= htmlspecialchars($location_name) ?>">
              <?php endforeach; ?>
          </datalist>
          <div class="invalid-feedback">Please enter location.</div>
        </div>
      </div>

      <h5 class="mb-3">Delivery Items</h5>
      <div class="table-responsive">
        <table class="table table-bordered" id="delivery_items_table">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th>SKU</th>
              <th>Unit</th>
              <th>Qty Left</th>
              <th>Qty Ordered</th>
              <th>Qty Delivered</th>
              <th>Outstanding</th>
              <th>Description</th>
              <th>Unit Price</th>
              <th>Taxed Price</th>
              <th>Non-Taxed Price</th>
              <th>Total (Non-Taxed)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="delivery_items_tbody">
            <!-- Rows will be populated dynamically -->
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" class="text-end fw-bold">Total Delivered:</td>
              <td class="text-center fw-bold" id="total_delivered_overall_display">0</td>
              <td colspan="7"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-success" id="add_delivery_item_btn">
          <i class="bi bi-plus-circle"></i> Add Item Line
        </button>
        <div>
          <a href="delivery_history.php" class="btn btn-secondary me-2">
            <i class="bi bi-x-circle"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> <?= $is_edit ? 'Update' : 'Generate' ?> Receipt
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('delivery_items_tbody');
    const addItemBtn = document.getElementById('add_delivery_item_btn');
    let itemRowIndex = 0;
    const inventoryItems = <?= json_encode($all_inventory_items) ?>;

    function createItemOptionsHtml() {
      let options = '<option value="">-- Select Inventory Item --</option>';
      inventoryItems.forEach(item => {
        const val = item.type + '_' + item.id;
        options += `<option value="${val}" 
                data-sku="${item.sku || ''}" 
                data-unit="${item.unit || ''}" 
                data-name="${item.name || ''}" 
                data-description="${item.description || ''}"
                data-unit_price="${item.unit_price || ''}"
                data-price_taxed="${item.price_taxed || ''}"
                data-price_nontaxed="${item.price_nontaxed || ''}"
                data-stock="${item.quantity || 0}">` +
          `[${item.type.charAt(0).toUpperCase() + item.type.slice(1)}] ${item.name} (SKU: ${(item.sku || 'N/A')}) - Stock: ${item.quantity}` +
          `</option>`;
      });
      options += '<option value="custom_item">-- Custom / Non-Stock Item --</option>';
      return options;
    }
    const itemOptionsHtmlContent = createItemOptionsHtml();

    function createDeliveryRow(data = {}) {
      const idx = itemRowIndex++;
      const newRow = document.createElement('tr');
      newRow.classList.add('delivery-item-row');
      newRow.innerHTML = `
              <td>
                  <select class="form-select master-item-select" name="line_items[${idx}][master_item_full_id]" required>
                      ${itemOptionsHtmlContent}
                  </select>
              </td>
              <td><input type="text" class="form-control item-sku" name="line_items[${idx}][sku]" value="${data.sku || ''}" readonly></td>
              <td><input type="text" class="form-control item-unit" name="line_items[${idx}][unit]" value="${data.unit || ''}" required readonly></td>
              <td><input type="text" class="form-control item-qty-left" name="line_items[${idx}][qty_left]" value="${data.qty_left || ''}" readonly></td>
              <td><input type="number" class="form-control item-ordered" name="line_items[${idx}][ordered]" value="${data.ordered || 1}" min="0.01" step="any" required></td>
              <td><input type="number" class="form-control item-delivered" name="line_items[${idx}][delivered]" value="${data.delivered || 0}" min="0" step="any" required></td>
              <td><input type="text" class="form-control item-outstanding" readonly></td>
              <td><input type="text" class="form-control item-description-display" name="line_items[${idx}][description]" value="${data.description || ''}" required></td>
              <td><input type="number" class="form-control item-unit-price" name="line_items[${idx}][unit_price]" value="${data.unit_price || ''}" min="0" step="any" readonly></td>
              <td><input type="number" class="form-control item-taxed-price" name="line_items[${idx}][price_taxed]" value="${data.price_taxed || ''}" min="0" step="any" readonly></td>
              <td><input type="number" class="form-control item-nontaxed-price" name="line_items[${idx}][price_nontaxed]" value="${data.price_nontaxed || ''}" min="0" step="any" readonly></td>
              <td><input type="number" class="form-control item-total-nontaxed" name="line_items[${idx}][total_nontaxed]" value="0" readonly></td>
              <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm remove-delivery-item"><i class="bi bi-trash"></i></button></td>
          `;
      tableBody.appendChild(newRow);
      attachDeliveryRowEventListeners(newRow);
      updateDeliveryRemoveButtons();
      calculateLineTotals(newRow);
    }

    function attachDeliveryRowEventListeners(row) {
      const masterSelect = row.querySelector('.master-item-select');
      const skuInput = row.querySelector('.item-sku');
      const unitInput = row.querySelector('.item-unit');
      const qtyLeftInput = row.querySelector('.item-qty-left');
      const descriptionInput = row.querySelector('.item-description-display');
      const orderedInput = row.querySelector('.item-ordered');
      const deliveredInput = row.querySelector('.item-delivered');
      const unitPriceInput = row.querySelector('.item-unit-price');
      const priceTaxedInput = row.querySelector('.item-taxed-price');
      const priceNontaxedInput = row.querySelector('.item-nontaxed-price');
      const totalNontaxedInput = row.querySelector('.item-total-nontaxed');

      masterSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value === 'custom_item') {
          skuInput.value = '';
          unitInput.value = '';
          qtyLeftInput.value = '';
          descriptionInput.value = '';
          unitPriceInput.value = '';
          priceTaxedInput.value = '';
          priceNontaxedInput.value = '';
          descriptionInput.readOnly = false;
          unitInput.readOnly = false;
          unitPriceInput.readOnly = false;
          priceTaxedInput.readOnly = false;
          priceNontaxedInput.readOnly = false;
        } else if (selectedOption) {
          skuInput.value = selectedOption.dataset.sku || '';
          unitInput.value = selectedOption.dataset.unit || '';
          qtyLeftInput.value = selectedOption.dataset.stock || '';
          descriptionInput.value = selectedOption.dataset.description || '';
          unitPriceInput.value = selectedOption.dataset.unit_price || '';
          priceTaxedInput.value = selectedOption.dataset.price_taxed || '';
          priceNontaxedInput.value = selectedOption.dataset.price_nontaxed || '';
          descriptionInput.readOnly = true;
          unitInput.readOnly = true;
          unitPriceInput.readOnly = true;
          priceTaxedInput.readOnly = true;
          priceNontaxedInput.readOnly = true;
        }
        calculateLineTotals(row);
      });

      [orderedInput, deliveredInput, priceNontaxedInput].forEach(input => {
        input.addEventListener('input', () => {
          calculateLineTotals(row);
          // Live update qty left (based on selected item minus delivered)
          const selectedOption = masterSelect.options[masterSelect.selectedIndex];
          const initialStock = parseFloat(selectedOption ? selectedOption.dataset.stock : 0) || 0;
          const delivered = parseFloat(deliveredInput.value) || 0;
          qtyLeftInput.value = (initialStock - delivered).toFixed(2);
        });
      });
    }

    function calculateLineTotals(row) {
      const ordered = parseFloat(row.querySelector('.item-ordered').value) || 0;
      const delivered = parseFloat(row.querySelector('.item-delivered').value) || 0;
      const priceNontaxed = parseFloat(row.querySelector('.item-nontaxed-price').value) || 0;

      if (delivered > ordered) {
        row.querySelector('.item-delivered').value = ordered;
      }
      const currentDelivered = parseFloat(row.querySelector('.item-delivered').value) || 0;
      row.querySelector('.item-outstanding').value = (ordered - currentDelivered).toFixed(2);
      row.querySelector('.item-total-nontaxed').value = (currentDelivered * priceNontaxed).toFixed(2);

      calculateOverallTotals();
    }

    function calculateOverallTotals() {
      let totalDeliveredOverall = 0;
      tableBody.querySelectorAll('tr.delivery-item-row').forEach(row => {
        totalDeliveredOverall += parseFloat(row.querySelector('.item-delivered').value) || 0;
      });
      document.getElementById('total_delivered_overall_display').textContent = totalDeliveredOverall.toFixed(2);
    }

    function updateDeliveryRemoveButtons() {
      const rows = tableBody.querySelectorAll('tr.delivery-item-row');
      rows.forEach((row, index) => {
        const removeBtn = row.querySelector('.remove-delivery-item');
        removeBtn.disabled = (rows.length === 1 && index === 0);
        removeBtn.onclick = function() {
          if (rows.length > 1) {
            row.remove();
            calculateOverallTotals();
            updateDeliveryRemoveButtons();
          }
        };
      });
    }

    if (addItemBtn) addItemBtn.addEventListener('click', () => createDeliveryRow());

    // Initial row
    if (tableBody.querySelectorAll('tr.delivery-item-row').length === 0) {
      createDeliveryRow();
    }

    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
      form.addEventListener('submit', function(event) {
        if (tableBody.querySelectorAll('tr.delivery-item-row').length === 0) {
          alert('Please add at least one delivery item.');
          event.preventDefault();
          event.stopPropagation();
          return;
        }
        let hasValidItem = false;
        tableBody.querySelectorAll('tr.delivery-item-row').forEach(row => {
          const desc = row.querySelector('.item-description-display').value;
          if (desc.trim() !== "") hasValidItem = true;
        });
        if (!hasValidItem) {
          alert('At least one delivery item must have a description.');
          event.preventDefault();
          event.stopPropagation();
          return;
        }
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    }
  });
</script>