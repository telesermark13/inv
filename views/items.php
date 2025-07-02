<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isset($conn)) {
    die("Database connection not found.");
}
$suppliers_list = [];
$suppliers_result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
if ($suppliers_result) {
    while ($supplier = $suppliers_result->fetch_assoc()) {
        $suppliers_list[] = $supplier;
    }
}
ob_start(); // Start output buffering

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize filter variables for main item list
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$min_qty = isset($_GET['min_qty']) && is_numeric($_GET['min_qty']) ? (int)$_GET['min_qty'] : null;
$max_qty = isset($_GET['max_qty']) && is_numeric($_GET['max_qty']) ? (int)$_GET['max_qty'] : null;

// Base query with prepared statements for main item list
$query = "SELECT * FROM items WHERE 1=1";
$params = [];
$types = '';

// Add filters to query for main item list
if (!empty($search_term)) {
    $searchTermWildcard = "%" . $conn->real_escape_string($search_term) . "%";
    $query .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $params[] = $searchTermWildcard;
    $params[] = $searchTermWildcard;
    $params[] = $searchTermWildcard;
    $types .= 'sss';
}
if ($min_price !== null) {
    $query .= " AND (price_taxed >= ? OR price_nontaxed >= ?)";
    $params[] = $min_price;
    $params[] = $min_price;
    $types .= 'dd';
}
if ($max_price !== null) {
    $query .= " AND (price_taxed <= ? OR price_nontaxed <= ?)";
    $params[] = $max_price;
    $params[] = $max_price;
    $types .= 'dd';
}
if ($min_qty !== null) {
    $query .= " AND quantity >= ?";
    $params[] = $min_qty;
    $types .= 'i';
}
if ($max_qty !== null) {
    $query .= " AND quantity <= ?";
    $params[] = $max_qty;
    $types .= 'i';
}
$query .= " ORDER BY name";

$stmt_items = $conn->prepare($query);
if (!empty($params)) {
    $stmt_items->bind_param($types, ...$params);
}
$stmt_items->execute();
$result = $stmt_items->get_result();

// if ($_SESSION['role'] !== 'admin') {
//     echo "<div class='card shadow mb-4'>";
//     echo "<div class='card-header bg-primary text-white'><h3 class='card-title mb-0'>Inventory</h3></div>";
//     echo "<div class='card-body'><div class='table-responsive'><table class='table table-bordered table-hover'>";
//     echo "<thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Quantity</th><th>Taxed Price</th><th>Non-Taxed Price</th></tr></thead><tbody>";
//     $non_admin_result_temp = $conn->query("SELECT id, name, description, quantity, price_taxed, price_nontaxed FROM items LIMIT 20");
//     while ($row_na = $non_admin_result_temp->fetch_assoc()) {
//         echo "<tr>
//                  <td>" . htmlspecialchars($row_na['id']) . "</td>
//                  <td>" . htmlspecialchars($row_na['name']) . "</td>
//                  <td>" . htmlspecialchars($row_na['description']) . "</td>
//                  <td>" . htmlspecialchars($row_na['quantity']) . "</td>
//                  <td>₱" . number_format($row_na['price_taxed'], 2) . "</td>
//                  <td>₱" . number_format($row_na['price_nontaxed'], 2) . "</td>
//                </tr>";
//     }
//     echo "</tbody></table></div></div></div>";
//     ob_end_flush();
//     return;
// }
// ?>

<div class="container-fluid">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Items Management</h3>
        </div>
        <div class="card-body">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Search & Filter</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="items.php" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search">Search</i></span>
                                <input type="text" class="form-control" name="search" placeholder="Search items..."
                                    value="<?= htmlspecialchars($search_term) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Price</span>
                                <input type="number" class="form-control" name="min_price" placeholder="Min"
                                    step="0.01" min="0" value="<?= htmlspecialchars($min_price ?? '') ?>">
                                <input type="number" class="form-control" name="max_price" placeholder="Max"
                                    step="0.01" min="0" value="<?= htmlspecialchars($max_price ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Qty</span>
                                <input type="number" class="form-control" name="min_qty" placeholder="Min"
                                    min="0" value="<?= htmlspecialchars($min_qty ?? '') ?>">
                                <input type="number" class="form-control" name="max_qty" placeholder="Max"
                                    min="0" value="<?= htmlspecialchars($max_qty ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Apply</button>
                            <a href="items.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stats_query_sql = "SELECT COUNT(*) as total_items, SUM(quantity) as total_quantity, SUM(unit_price * IF(taxable = 1, 1.12, 1) * quantity) as total_taxed_value, SUM(unit_price * quantity) as total_nontaxed_value FROM items WHERE 1=1";
                        $stats_params_array = [];
                        $stats_types_str = '';

                        if (!empty($search_term)) {
                            $searchTermWildcardStats = "%" . $conn->real_escape_string($search_term) . "%";
                            $stats_query_sql .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
                            $stats_params_array[] = $searchTermWildcardStats;
                            $stats_params_array[] = $searchTermWildcardStats;
                            $stats_params_array[] = $searchTermWildcardStats;
                            $stats_types_str .= 'sss';
                        }
                        if ($min_price !== null) {
                            $stats_query_sql .= " AND (price_taxed >= ? OR price_nontaxed >= ?)";
                            $stats_params_array[] = $min_price;
                            $stats_params_array[] = $min_price;
                            $stats_types_str .= 'dd';
                        }
                        if ($max_price !== null) {
                            $stats_query_sql .= " AND (price_taxed <= ? OR price_nontaxed <= ?)";
                            $stats_params_array[] = $max_price;
                            $stats_params_array[] = $max_price;
                            $stats_types_str .= 'dd';
                        }
                        if ($min_qty !== null) {
                            $stats_query_sql .= " AND quantity >= ?";
                            $stats_params_array[] = $min_qty;
                            $stats_types_str .= 'i';
                        }
                        if ($max_qty !== null) {
                            $stats_query_sql .= " AND quantity <= ?";
                            $stats_params_array[] = $max_qty;
                            $stats_types_str .= 'i';
                        }

                        $stmt_stats = $conn->prepare($stats_query_sql);
                        if (!empty($stats_params_array)) {
                            $stmt_stats->bind_param($stats_types_str, ...$stats_params_array);
                        }
                        $stmt_stats->execute();
                        $stats_result = $stmt_stats->get_result();
                        $stats = $stats_result->fetch_assoc();
                        $stmt_stats->close();
                        ?>
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light p-3">
                                    <h6 class="text-muted">Filtered Items</h6>
                                    <h4><?= $stats['total_items'] ?? 0 ?></h4>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light p-3">
                                    <h6 class="text-muted">Total Quantity</h6>
                                    <h4><?= $stats['total_quantity'] ?? 0 ?></h4>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light p-3">
                                    <h6 class="text-muted">Total Taxed Value</h6>
                                    <h4>₱<?= number_format($stats['total_taxed_value'] ?? 0, 2) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light p-3">
                                    <h6 class="text-muted">Total Non-Taxed Value</h6>
                                    <h4>₱<?= number_format($stats['total_nontaxed_value'] ?? 0, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mt-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Current Inventory</h5>
                <div class="text-muted small">Showing <?= $result->num_rows ?> items</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Serial No.</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Qty</th>
                                <th>Min Stock Level</th>
                                <th>Unit Price</th>
                                <th>Taxed Price</th>
                                <th>Non-Taxed Price</th>
                                <th>Total Value (Non-Taxed)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grand_total_taxed = 0;
                            $grand_total_nontaxed = 0;
                            while ($row = $result->fetch_assoc()):
                                $unit_price = $row['unit_price'];
                                $quantity = $row['quantity'];
                                $min_stock_level = $row['min_stock_level'];
                                $taxable = $row['taxable'];

                                // Calculate on the fly:
                                $price_taxed = $taxable ? ($unit_price * 1.12) : $unit_price;
                                $price_nontaxed = $unit_price;
                                $total_value_nontaxed = $price_nontaxed * $quantity;
                                $total_value_taxed = $price_taxed * $quantity;

                                // Add to running totals
                                $grand_total_taxed += $total_value_taxed;
                                $grand_total_nontaxed += $total_value_nontaxed;
                            ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['sku'] ?? 'N/A') ?></td>
                                    <td><?= nl2br(htmlspecialchars(substr($row['description'] ?? '', 0, 50))) . (strlen($row['description'] ?? '') > 50 ? '...' : '') ?></td>
                                    <td><?= htmlspecialchars($row['unit']) ?></td>
                                    <td><?= $quantity ?></td>
                                    <td>
                                        <?php if ($quantity <= $min_stock_level && $min_stock_level > 0): ?>
                                            <span class="badge bg-danger"><?= $min_stock_level ?></span>
                                        <?php else: ?>
                                            <?= $min_stock_level ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format($unit_price, 2) ?></td>
                                    <td>₱<?= number_format($price_taxed, 2) ?></td>
                                    <td>₱<?= number_format($price_nontaxed, 2) ?></td>
                                    <td>₱<?= number_format($total_value_nontaxed, 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f5f5f5; font-weight: bold;">
                                <td colspan="8" class="text-end">Grand Total:</td>
                                <td>₱<?= number_format($grand_total_taxed, 2) ?></td>
                                <td>₱<?= number_format($grand_total_nontaxed, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                </table>
            </div>
        </div>
    </div>

    <!-- Delivered Items History section (unchanged, keep as is) -->
    <div class="card shadow mt-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Delivered Items History</h5>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#deliveryFiltersCollapse">
                <i class="fas fa-filter"></i> Filters
            </button>
        </div>
        <div class="collapse" id="deliveryFiltersCollapse">
            <div class="card-body border-bottom">
                <form method="GET" action="items.php#deliveryFiltersCollapse" class="row g-3">
                    <div class="col-md-3"><label class="form-label">Delivery #</label><input type="text" class="form-control" name="filter_delivery_number" value="<?= htmlspecialchars($_GET['filter_delivery_number'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Date From</label><input type="date" class="form-control" name="filter_date_from" value="<?= htmlspecialchars($_GET['filter_date_from'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Date To</label><input type="date" class="form-control" name="filter_date_to" value="<?= htmlspecialchars($_GET['filter_date_to'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Client</label><input type="text" class="form-control" name="filter_client" value="<?= htmlspecialchars($_GET['filter_client'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Project</label><input type="text" class="form-control" name="filter_project" value="<?= htmlspecialchars($_GET['filter_project'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Item Desc.</label><input type="text" class="form-control" name="filter_item_desc" value="<?= htmlspecialchars($_GET['filter_item_desc'] ?? '') ?>"></div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Apply Delivery Filters</button>
                        <a href="items.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear Delivery Filters</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            
            <form method="GET" class="mb-2" id="delivery-page-size-form">
                <?php foreach ($_GET as $key => $val): 
                 if ($key !== 'delivery_page_size' && $key !== 'delivery_page') { ?>
                     <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                <?php } endforeach; ?>
                <label>
                    Show 
                    <select name="delivery_page_size" onchange="document.getElementById('delivery-page-size-form').submit();">
                        <?php foreach ($page_size_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= $opt == $page_size ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    entries
                </label>
            </form>
            <?php if ($total_delivery_items > 0): ?>
        <p class="small text-muted mb-2">
            Showing
            <?= $total_delivery_items == 0 ? 0 : ($delivery_offset + 1) ?>
            to
            <?= min($delivery_offset + $page_size, $total_delivery_items) ?>
            of
            <?= $total_delivery_items ?>
            entries
        </p>
    <?php endif; ?>
    <table class="table table-bordered table-hover" id="deliveredItemsTable">
                <table class="table table-bordered table-hover" id="deliveredItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Delivery #</th>
                            <th>Date</th>
                            <th>Item Description</th>
                            <th>Qty Delivered</th>
                            <th>Unit</th>
                            <th>Client</th>
                            <th>Project</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $delivery_base_query = "SELECT di.delivery_number, di.item_description, di.delivered, di.unit, dr.date, dr.client, dr.project
                                                    FROM delivered_items di
                                                    JOIN delivery_receipts dr ON di.delivery_number = dr.delivery_number
                                                    WHERE 1=1";
                        $delivery_params_array = [];
                        $delivery_types_str = '';
                        // --- PAGINATION FOR DELIVERED ITEMS HISTORY ---
                        $page_size_options = [10, 20, 50];
                        $page_size = isset($_GET['delivery_page_size']) && in_array((int)$_GET['delivery_page_size'], $page_size_options)
                            ? (int)$_GET['delivery_page_size'] : 10;
                        $page_num = isset($_GET['delivery_page']) && is_numeric($_GET['delivery_page']) && (int)$_GET['delivery_page'] > 0
                            ? (int)$_GET['delivery_page'] : 1;
                        $delivery_offset = ($page_num - 1) * $page_size;
                        // Build total count query with same filters
                        $delivery_count_query = "SELECT COUNT(*) as total FROM delivered_items di
                            JOIN delivery_receipts dr ON di.delivery_number = dr.delivery_number
                            WHERE 1=1";
                        $delivery_count_params = [];
                        $delivery_count_types = '';

                        if (!empty($_GET['filter_delivery_number'])) {
                            $delivery_count_query .= " AND di.delivery_number = ?";
                            $delivery_count_params[] = (int)$_GET['filter_delivery_number'];
                            $delivery_count_types .= 'i';
                        }
                        if (!empty($_GET['filter_date_from'])) {
                            $delivery_count_query .= " AND dr.date >= ?";
                            $delivery_count_params[] = $_GET['filter_date_from'];
                            $delivery_count_types .= 's';
                        }
                        if (!empty($_GET['filter_date_to'])) {
                            $delivery_count_query .= " AND dr.date <= ?";
                            $delivery_count_params[] = $_GET['filter_date_to'];
                            $delivery_count_types .= 's';
                        }
                        if (!empty($_GET['filter_client'])) {
                            $delivery_count_query .= " AND dr.client LIKE ?";
                            $delivery_count_params[] = "%" . $conn->real_escape_string($_GET['filter_client']) . "%";
                            $delivery_count_types .= 's';
                        }
                        if (!empty($_GET['filter_project'])) {
                            $delivery_count_query .= " AND dr.project LIKE ?";
                            $delivery_count_params[] = "%" . $conn->real_escape_string($_GET['filter_project']) . "%";
                            $delivery_count_types .= 's';
                        }
                        if (!empty($_GET['filter_item_desc'])) {
                            $delivery_count_query .= " AND di.item_description LIKE ?";
                            $delivery_count_params[] = "%" . $conn->real_escape_string($_GET['filter_item_desc']) . "%";
                            $delivery_count_types .= 's';
                        }

                        $stmt_delivery_count = $conn->prepare($delivery_count_query);
                        if (!empty($delivery_count_params)) {
                            $stmt_delivery_count->bind_param($delivery_count_types, ...$delivery_count_params);
                        }
                        $stmt_delivery_count->execute();
                        $total_delivery_items = $stmt_delivery_count->get_result()->fetch_assoc()['total'] ?? 0;
                        $stmt_delivery_count->close();
                        $total_pages = max(1, ceil($total_delivery_items / $page_size));

                        if (!empty($_GET['filter_delivery_number'])) {
                            $delivery_base_query .= " AND di.delivery_number = ?";
                            $delivery_params_array[] = (int)$_GET['filter_delivery_number'];
                            $delivery_types_str .= 'i';
                        }
                        if (!empty($_GET['filter_date_from'])) {
                            $delivery_base_query .= " AND dr.date >= ?";
                            $delivery_params_array[] = $_GET['filter_date_from'];
                            $delivery_types_str .= 's';
                        }
                        if (!empty($_GET['filter_date_to'])) {
                            $delivery_base_query .= " AND dr.date <= ?";
                            $delivery_params_array[] = $_GET['filter_date_to'];
                            $delivery_types_str .= 's';
                        }
                        if (!empty($_GET['filter_client'])) {
                            $filterClientWildcard = "%" . $conn->real_escape_string($_GET['filter_client']) . "%";
                            $delivery_base_query .= " AND dr.client LIKE ?";
                            $delivery_params_array[] = $filterClientWildcard;
                            $delivery_types_str .= 's';
                        }
                        if (!empty($_GET['filter_project'])) {
                            $filterProjectWildcard = "%" . $conn->real_escape_string($_GET['filter_project']) . "%";
                            $delivery_base_query .= " AND dr.project LIKE ?";
                            $delivery_params_array[] = $filterProjectWildcard;
                            $delivery_types_str .= 's';
                        }
                        if (!empty($_GET['filter_item_desc'])) {
                            $filterItemDescWildcard = "%" . $conn->real_escape_string($_GET['filter_item_desc']) . "%";
                            $delivery_base_query .= " AND di.item_description LIKE ?";
                            $delivery_params_array[] = $filterItemDescWildcard;
                            $delivery_types_str .= 's';
                        }
                      $delivery_base_query .= " ORDER BY dr.date DESC, di.delivery_number DESC LIMIT ? OFFSET ?";
                        $delivery_params_array[] = $page_size;
                        $delivery_types_str .= 'i';
                        $delivery_params_array[] = $delivery_offset;
                        $delivery_types_str .= 'i';


                        $stmt_delivered = $conn->prepare($delivery_base_query);
                        if (!empty($delivery_params_array)) {
                            $stmt_delivered->bind_param($delivery_types_str, ...$delivery_params_array);
                        }
                        $stmt_delivered->execute();
                        $delivered_items_result = $stmt_delivered->get_result();

                        while ($item_del = $delivered_items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($item_del['delivery_number']) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime($item_del['date']))) ?></td>
                                <td><?= htmlspecialchars($item_del['item_description']) ?></td>
                                <td><?= htmlspecialchars($item_del['delivered']) ?></td>
                                <td><?= htmlspecialchars($item_del['unit']) ?></td>
                                <td><?= htmlspecialchars($item_del['client']) ?></td>
                                <td><?= htmlspecialchars($item_del['project']) ?></td>
                            </tr>
                        <?php endwhile;
                        $stmt_delivered->close(); ?>
                    </tbody>
                    <script>
                        $(document).ready(function() {
                            $('#addSupplierRow').click(function() {
                                var rowHtml = `
      <div class="row mb-2 supplier-price-row">
        <div class="col-md-5">
          <select name="supplier_ids[]" class="form-select" required>
            <option value="">Select supplier</option>
            <?php foreach ($suppliers_list as $supplier): ?>
              <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <input type="number" step="0.01" min="0" name="supplier_unit_prices[]" class="form-control" placeholder="Unit Price" required>
        </div>
        <div class="col-md-3">
          <input type="number" step="0.01" min="0" name="supplier_price_taxed[]" class="form-control" placeholder="Taxed Price">
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-danger btn-sm remove-supplier-row" tabindex="-1">&times;</button>
        </div>
      </div>
    `;
                                $('#supplierPricesWrapper').append(rowHtml);
                            });

                            $(document).on('click', '.remove-supplier-row', function() {
                                $(this).closest('.supplier-price-row').remove();
                            });
                        });
                    </script>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                                            <li class="page-item <?= $p == $page_num ? 'active' : '' ?>">
                                        <a class="page-link"
                                         href="?<?= http_build_query(array_merge($_GET, ['delivery_page' => $p, 'delivery_page_size' => $page_size])) ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>

                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>