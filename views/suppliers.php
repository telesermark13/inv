<?php
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><?= htmlspecialchars($page_title ?? 'Suppliers Management') ?></h3>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#supplierModal" id="addSupplierBtn">
                <i class="fas fa-plus"></i> Add Supplier
            </button>
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
                <table class="table table-bordered table-hover datatable" id="suppliersTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($suppliers_data)): ?>
                            <?php foreach($suppliers_data as $supplier): ?>
                            <tr>
                                <td><?= $supplier['id'] ?></td>
                                <td><?= htmlspecialchars($supplier['name']) ?></td>
                                <td><?= htmlspecialchars($supplier['contact_person'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($supplier['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($supplier['phone'] ?? 'N/A') ?></td>
                                <td><?= nl2br(htmlspecialchars(substr($supplier['address'] ?? '', 0, 70))) . (strlen($supplier['address'] ?? '') > 70 ? '...' : '') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-supplier-btn"
                                        data-id="<?= $supplier['id'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#supplierModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="delete_supplier.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier? This might affect related purchase orders or material requests.')">
                                        <input type="hidden" name="supplier_id" value="<?= $supplier['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No suppliers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalLabel">Add/Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="supplierForm" method="POST" action="save_supplier.php" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id" id="supplier_id_for_edit" value="0">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplierName" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="supplierName" name="name" required>
                                <div class="invalid-feedback">Supplier name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="supplierContactPerson" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="supplierContactPerson" name="contact_person">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplierEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="supplierEmail" name="email">
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="supplierPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="supplierPhone" name="phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="supplierAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="supplierAddress" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveSupplierBtn">Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.datatable').DataTable({responsive: true});

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
    
    $('#addSupplierBtn').on('click', function() {
        $('#supplierModalLabel').text('Add New Supplier');
        $('#supplierForm').trigger("reset").removeClass('was-validated');
        $('#supplier_id_for_edit').val('0'); // This now matches the PHP
    });

    $('.edit-supplier-btn').click(function() {
        const supplierId = $(this).data('id');
        $('#supplierModalLabel').text('Edit Supplier');
        $('#supplierForm').trigger("reset").removeClass('was-validated');
        $('#supplier_id_for_edit').val(supplierId);

        // AJAX call to get supplier details
        $.ajax({
            url: 'get_supplier_details.php', // You will need to create this AJAX handler
            type: 'GET',
            data: { id: supplierId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const supplier = response.data;
                    $('#supplierName').val(supplier.name);
                    $('#supplierContactPerson').val(supplier.contact_person);
                    $('#supplierEmail').val(supplier.email);
                    $('#supplierPhone').val(supplier.phone);
                    $('#supplierAddress').val(supplier.address);
                } else {
                    alert('Error fetching supplier details: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('AJAX error: Could not fetch supplier details.');
            }
        });
    });
});
</script>
