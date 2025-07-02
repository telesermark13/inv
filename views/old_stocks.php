<?php
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><?= htmlspecialchars($page_title ?? 'Old Stocks Management') ?></h3>
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><?= htmlspecialchars($page_title ?? 'Old Stocks Management') ?></h3>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#oldStockModal" id="addOldStockBtn">
                        <i class="fas fa-plus"></i> Add Old Stock Item
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" id="oldStocksTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Min Stock Level</th>
                            <th>Supplier</th>
                            <th>Date Acquired</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($old_stocks_data)): ?>
                            <?php foreach ($old_stocks_data as $stock): ?>
                                <tr>
                                    <td><?= $stock['id'] ?></td>
                                    <td><?= htmlspecialchars($stock['sku'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($stock['name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars(substr($stock['description'] ?? '', 0, 50))) . (strlen($stock['description'] ?? '') > 50 ? '...' : '') ?></td>
                                    <td><?= htmlspecialchars($stock['quantity']) ?></td>
                                    <td><?= htmlspecialchars($stock['unit']) ?></td>
                                    <td>₱<?= number_format($stock['unit_price'], 2) ?></td>
                                    <td><?= htmlspecialchars($stock['min_stock_level']) ?></td>
                                    <td><?= htmlspecialchars($stock['supplier_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($stock['date_acquired'] ? date('M d, Y', strtotime($stock['date_acquired'])) : 'N/A') ?></td>
                                    <td>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                            <button class="btn btn-sm btn-info edit-oldstock-btn"
                                                data-id="<?= $stock['id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#oldStockModal">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" action="delete_old_stock.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this old stock item? This action cannot be undone.')">
                                                <input type="hidden" name="old_stock_id" value="<?= $stock['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No old stock items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="oldStockModal" tabindex="-1" aria-labelledby="oldStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="oldStockModalLabel">Add/Edit Old Stock Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="oldStockForm" method="POST" action="save_old_stock.php" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="old_stock_id_for_edit" id="old_stock_id_for_edit" value="0">

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="os_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="os_name" name="name" required>
                                <div class="invalid-feedback">Item name is required.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="os_sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="os_sku" name="sku">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="os_description" class="form-label">Description</label>
                            <textarea class="form-control" id="os_description" name="description" rows="2"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="os_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="os_quantity" name="quantity" min="0" step="any" required>
                                <div class="invalid-feedback">Quantity is required (can be 0).</div>
                            </div>
                            <div class="col-md-3">
                                <label for="os_unit" class="form-label">Unit <span class="text-danger">*</span></label>
                                <select class="form-select" id="os_unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="pc">Piece (pc)</option>
                                    <option value="set">Set</option>
                                    <option value="box">Box</option>
                                    <option value="pack">Pack (pck)</option>
                                    <option value="roll">Roll</option>
                                    <option value="mtrs">Meters (mtrs)</option>
                                    <option value="ft">Feet (ft)</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="g">Gram (g)</option>
                                    <option value="ltr">Liter (ltr)</option>
                                    <option value="ml">Milliliter (ml)</option>
                                    <option value_other>Other</option>
                                </select>
                                <div class="invalid-feedback">Unit is required.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="os_unit_price" class="form-label">Unit Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="os_unit_price" name="unit_price" min="0" step="any" required>
                                </div>
                                <div class="invalid-feedback">Unit price is required.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="os_date_acquired" class="form-label">Date Acquired</label>
                                <input type="date" class="form-control" id="os_date_acquired" name="date_acquired" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="os_supplier_id" class="form-label">Supplier (Optional)</label>
                                <select class="form-select" id="os_supplier_id" name="supplier_id">
                                    <option value="">-- Select Supplier --</option>
                                    <?php if (!empty($suppliers_list)): ?>
                                        <?php foreach ($suppliers_list as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <!-- add Min stock level -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="os_min_stock_level" class="form-label">Min Stock Level <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="os_min_stock_level" name="min_stock_level" min="0" value="10" required>
                                <div class="invalid-feedback">Min stock level is required.</div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveOldStockBtn">Save Old Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            responsive: true
        });

        // Bootstrap form validation
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

        $('#addOldStockBtn').on('click', function() {
            $('#oldStockModalLabel').text('Add New Old Stock Item');
            $('#oldStockForm').trigger("reset").removeClass('was-validated');
            $('#old_stock_id_for_edit').val('0');
            $('#os_date_acquired').val('<?= date('Y-m-d') ?>'); // Default to today
        });

        $('.edit-oldstock-btn').click(function() {
            const stockId = $(this).data('id');
            $('#oldStockModalLabel').text('Edit Old Stock Item');
            $('#oldStockForm').trigger("reset").removeClass('was-validated');
            $('#old_stock_id_for_edit').val(stockId);

            // AJAX call to get old stock details
            $.ajax({
                url: 'get_old_stock_details.php', // You will need to create this AJAX handler
                type: 'GET',
                data: {
                    id: stockId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const stock = response.data;
                        $('#os_min_stock_level').val(stock.min_stock_level || 10);
                        $('#os_name').val(stock.name);
                        $('#os_sku').val(stock.sku);
                        $('#os_description').val(stock.description);
                        $('#os_quantity').val(stock.quantity);
                        $('#os_unit').val(stock.unit);
                        $('#os_unit_price').val(stock.unit_price);
                        $('#os_supplier_id').val(stock.supplier_id || '');
                        $('#os_date_acquired').val(stock.date_acquired ? stock.date_acquired.split(' ')[0] : ''); // Format for date input
                    } else {
                        alert('Error fetching old stock details: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('AJAX error: Could not fetch old stock details.');
                }
            });
        });
    });
</script>