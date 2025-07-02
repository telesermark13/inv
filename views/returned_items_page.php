<?php
// --- DEBUG: Log all POST data (delete this after testing!) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('debug_add_return.log', print_r($_POST, true)); // Remove this after checking!
}
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
            <i class="bi bi-arrow-repeat me-2"></i>
            Returned Items Management
        </h3>
        <button class="btn btn-success d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addReturnModal">
            <i class="bi bi-plus-circle"></i> <span>Add New Return</span>
        </button>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" id="returnedItemsTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Return Date</th>
                        <th>Item Name (at return)</th>
                        <th>SKU (at return)</th>
                        <th>Qty Returned</th>
                        <th>Reason</th>
                        <th>Returned By</th>
                        <th>Customer Name</th>
                        <th>Location</th>
                        <th>Received By</th>
                        <th style="width:115px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($returned_items_data)): ?>
                        <?php foreach ($returned_items_data as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($row['return_date']))) ?></td>
                                <td><?= htmlspecialchars($row['name_at_return']) ?></td>
                                <td><?= htmlspecialchars($row['sku_at_return'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['quantity_returned']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['reason'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($row['returned_by_username'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['location'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['received_by_username'] ?? '') ?></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button class="btn btn-sm btn-primary edit-return-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editReturnModal">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <form method="POST" action="return_item_actions.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this return record? This will also adjust item stock if the action is configured to do so.');"
                                            style="margin:0; padding:0;">
                                            <input type="hidden" name="action" value="delete_return">
                                            <input type="hidden" name="return_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="item_id_for_stock_revert" value="<?= $row['item_id'] ?>">
                                            <input type="hidden" name="quantity_to_revert" value="<?= $row['quantity_returned'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted">No returned items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Return Modal -->
<div class="modal fade" id="addReturnModal" tabindex="-1" aria-labelledby="addReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addReturnModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Item Return
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addReturnForm" method="POST" action="return_item_actions.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_return">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="mb-3">
                        <label for="addItemId" class="form-label">Select Item to Return <span class="text-danger">*</span></label>
                        <select class="form-select" id="addItemId" name="item_id" required>
                            <option value="">-- Select Item --</option>
                            <?php foreach ($items_list as $item): ?>
                                <option value="<?= $item['id'] ?>"
                                    data-name="<?= htmlspecialchars($item['name']) ?>"
                                    data-sku="<?= htmlspecialchars($item['sku'] ?? '') ?>"
                                    data-unit="<?= htmlspecialchars($item['unit'] ?? '') ?>"
                                    data-unit_price="<?= htmlspecialchars($item['unit_price'] ?? '') ?>"
                                    data-price_taxed="<?= htmlspecialchars($item['price_taxed'] ?? '') ?>"
                                    data-price_nontaxed="<?= htmlspecialchars($item['price_nontaxed'] ?? '') ?>"
                                    data-description="<?= htmlspecialchars($item['description'] ?? '') ?>">
                                    <?= htmlspecialchars($item['name']) ?> (SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?>) - Stock: <?= $item['quantity'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select an item.</div>
                    </div>
                    <!-- Live preview -->
                    <div class="mb-3" id="selectedItemDetailsAdd" style="display:none;">
                        <div><strong>Name:</strong> <span id="detailNameAdd"></span></div>
                        <div><strong>SKU:</strong> <span id="detailSkuAdd"></span></div>
                        <div><strong>Unit:</strong> <span id="detailUnitAdd"></span></div>
                        <div><strong>Unit Price:</strong> <span id="detailUnitPriceAdd"></span></div>
                    </div>
                    <!-- Hidden fields for item snapshot -->
                    <input type="hidden" name="name_at_return" id="addNameAtReturn">
                    <input type="hidden" name="sku_at_return" id="addSkuAtReturn">
                    <input type="hidden" name="description_at_return" id="addDescriptionAtReturn">
                    <input type="hidden" name="unit_at_return" id="addUnitAtReturn">
                    <input type="hidden" name="unit_price_at_return" id="addUnitPriceAtReturn">
                    <input type="hidden" name="price_taxed_at_return" id="addPriceTaxedAtReturn">
                    <input type="hidden" name="price_nontaxed_at_return" id="addPriceNontaxedAtReturn">
                    <div class="mb-3">
                        <label for="addQuantityReturned" class="form-label">Quantity Returned <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="addQuantityReturned" name="quantity_returned" min="1" required>
                        <div class="invalid-feedback">Please enter a valid quantity.</div>
                    </div>
                    <div class="mb-3">
                        <label for="addReason" class="form-label">Reason for Return</label>
                        <textarea class="form-control" id="addReason" name="reason"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="addReturnedBy" class="form-label">Returned By (User)</label>
                            <select class="form-select" id="addReturnedBy" name="returned_by">
                                <option value="">-- Select User --</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($_SESSION['user_id'] ?? null) == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="addReceivedBy" class="form-label">Received By (User) <span class="text-danger">*</span></label>
                            <select class="form-select" id="addReceivedBy" name="received_by" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($_SESSION['user_id'] ?? null) == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select who received the item.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="addCustomerName" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="addCustomerName" name="customer_name">
                    </div>
                    <div class="mb-3">
                        <label for="addLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="addLocation" name="location">
                    </div>
                    <div class="mb-3">
                        <label for="addReturnDate" class="form-label">Return Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="addReturnDate" name="return_date" required value="<?= date('Y-m-d\TH:i') ?>">
                        <div class="invalid-feedback">Please select a valid return date and time.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Return Modal (for JS to fill in) -->
<div class="modal fade" id="editReturnModal" tabindex="-1" aria-labelledby="editReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editReturnModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Item Return
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editReturnForm" method="POST" action="return_item_actions.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_return">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="return_id" id="editReturnId">
                    <input type="hidden" name="original_item_id_for_edit" id="editOriginalItemId">

                    <div class="mb-3">
                        <label class="form-label">Item Originally Returned:</label>
                        <p id="editItemInfo" class="form-control-plaintext bg-light p-2 border rounded"></p>
                    </div>
                    <div class="mb-3">
                        <label for="editQuantityReturned" class="form-label">Quantity Returned <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="editQuantityReturned" name="quantity_returned" min="1" step="any" required>
                        <input type="hidden" id="editOriginalQuantityReturned">
                        <div class="invalid-feedback">Please enter a valid quantity.</div>
                    </div>
                    <div class="mb-3">
                        <label for="editReason" class="form-label">Reason for Return</label>
                        <textarea class="form-control" id="editReason" name="reason" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editReturnedBy" class="form-label">Returned By (User)</label>
                            <select class="form-select" id="editReturnedBy" name="returned_by">
                                <option value="">-- Select User --</option>
                                <?php if (!empty($users_list)): ?>
                                    <?php foreach ($users_list as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editReceivedBy" class="form-label">Received By (User) <span class="text-danger">*</span></label>
                            <select class="form-select" id="editReceivedBy" name="received_by" required>
                                <option value="">-- Select User --</option>
                                <?php if (!empty($users_list)): ?>
                                    <?php foreach ($users_list as $user): ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">Please select who received the item.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editReturnDate" class="form-label">Return Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="editReturnDate" name="return_date" required>
                        <div class="invalid-feedback">Please select a valid return date and time.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Make sure to include jQuery, Bootstrap, and DataTables JS *in this order* -->
<script>
    $(function() {
        $('#addReturnForm, #editReturnForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);

            // For Bootstrap validation
            if (!this.checkValidity()) {
                form.addClass('was-validated');
                return;
            }

            var formData = form.serialize();
            var submitBtn = form.find('[type="submit"]');
            var originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message || 'Success!');
                        $('.modal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 800); // Refresh after 800ms for UX
                    } else {
                        showAlert('danger', response.message || 'Failed!');
                    }
                },
                error: function(xhr) {
                    showAlert('danger', 'Error: ' + (xhr.responseJSON?.message || 'Server error occurred'));
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        function showAlert(type, message) {
            const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>`;
            $('.content').prepend(alertHtml);
        }

        $('.datatable').DataTable({
            responsive: true,
            order: [
                [1, 'desc']
            ]
        });

        // Bootstrap 5 form validation
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

        // --- Add Return Modal: Item Selection Logic ---
        $('#addItemId').on('change', function() {
            const opt = $(this).find('option:selected');
            $('#addNameAtReturn').val(opt.data('name'));
            $('#addSkuAtReturn').val(opt.data('sku'));
            $('#addDescriptionAtReturn').val(opt.data('description'));
            $('#addUnitAtReturn').val(opt.data('unit'));
            $('#addUnitPriceAtReturn').val(opt.data('unit_price'));
            $('#addPriceTaxedAtReturn').val(opt.data('price_taxed'));
            $('#addPriceNontaxedAtReturn').val(opt.data('price_nontaxed'));

            // Optional: live preview for selected item
            if (opt.val() && opt.val() !== "") {
                $('#detailNameAdd').text(opt.data('name'));
                $('#detailSkuAdd').text(opt.data('sku') || 'N/A');
                $('#detailUnitAdd').text(opt.data('unit'));
                $('#detailUnitPriceAdd').text(parseFloat(opt.data('unit_price')).toFixed(2));
                $('#selectedItemDetailsAdd').show();
            } else {
                $('#selectedItemDetailsAdd').hide();
            }
        });
        $('#addReturnModal').on('shown.bs.modal', function() {
            $('#addItemId').trigger('change');
        });

        // --- Edit Return Modal: Populate Form ---
        $('.edit-return-btn').on('click', function() {
            const returnId = $(this).data('id');
            $('#editReturnForm')[0].reset();
            $('#editReturnForm').removeClass('was-validated');
            $.ajax({
                url: 'return_item_actions.php',
                type: 'GET',
                data: {
                    action: 'get_return_details',
                    return_id: returnId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const record = response.data;
                        $('#editReturnId').val(record.id);
                        $('#editOriginalItemId').val(record.item_id);
                        $('#editItemInfo').html(
                            `<strong>Name:</strong> ${htmlspecialchars(record.name_at_return)}<br>` +
                            `<strong>SKU:</strong> ${htmlspecialchars(record.sku_at_return || 'N/A')}<br>` +
                            `<strong>Unit:</strong> ${htmlspecialchars(record.unit_at_return || 'N/A')}`
                        );
                        $('#editQuantityReturned').val(record.quantity_returned);
                        $('#editOriginalQuantityReturned').val(record.quantity_returned);
                        $('#editReason').val(record.reason || '');
                        let returnDateISO = record.return_date.replace(' ', 'T').substring(0, 16);
                        $('#editReturnDate').val(returnDateISO);
                        $('#editReturnedBy').val(record.returned_by || '');
                        $('#editReceivedBy').val(record.received_by || '');
                    } else {
                        alert('Error fetching return details: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error fetching return details: ' + error);
                }
            });
        });

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
</script>