<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check admin status
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: items.php");
    exit;
}

// Get item data
$item = null;
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
}

if (!$item) {
    $_SESSION['error'] = "Item not found!";
    header("Location: items.php");
    exit;
}
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Edit Item</h3>
        </div>
        <div class="card-body">
            <form id="editItemForm" method="POST" action="update_item.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?= htmlspecialchars($item['name']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="form-control" name="quantity" 
                                   value="<?= $item['quantity'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unit</label>
                            <select class="form-select" name="unit" required>
                                <?php
                                $units = ['pcs', 'pc', 'pr', 'assy', 'unt', 'unts', 'mtrs', 'ft', 'box', 'pck'];
                                foreach ($units as $u) {
                                    echo "<option value='$u' " . ($item['unit'] === $u ? 'selected' : '') . ">" 
                                         . ucfirst($u) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>SKU (Optional)</label>
                            <input type="text" class="form-control" name="sku"
                                   value="<?= htmlspecialchars($item['sku'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Min. Stock Level</label>
                            <input type="number" class="form-control" name="min_stock_level"
                                   value="<?= $item['min_stock_level'] ?? 0 ?>" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" required><?= 
                                htmlspecialchars($item['description']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unit Price (₱)</label>
                            <input type="number" class="form-control" name="unit_price" 
                                   step="0.01" min="0" value="<?= $item['unit_price'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Taxable</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" name="taxable" value="1"
                                    <?php
                                    // Infer taxable status if price_taxed > price_nontaxed (which is unit_price)
                                    // Ensure values are float for comparison
                                    echo (floatval($item['price_taxed']) > floatval($item['price_nontaxed'])) ? 'checked' : '';
                                    ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="items.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editItemForm');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Failed to update item');
            }

            window.location.href = 'items.php?success=Item updated successfully';
        } catch (error) {
            console.error('Error:', error);
            alert(`Error: ${error.message}`);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Item';
        }
    });
});
</script>