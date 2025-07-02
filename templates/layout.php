<?php
if (session_status() == PHP_SESSION_NONE) session_start();
$user_role = $_SESSION['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>


  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Inventory Management System">

  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" crossorigin="anonymous">
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" as="style">

  <title><?= htmlspecialchars($page_title ?? 'Inventory System') ?></title>
  <link rel="icon" href="<?= $base_url ?? '' ?>assets/favicon.ico" type="image/x-icon">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= $base_url ?? '' ?>assets/styles.css?v=<?= filemtime('assets/styles.css') ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <style>
    :root {
      --sidebar-width: 280px;
      --primary-color: #0d6efd;
      --sidebar-bg: linear-gradient(135deg, #1a1e23 0%, #2a2f37 100%);
      --sidebar-active: rgba(13, 110, 253, 0.2);
      --sidebar-hover: rgba(255, 255, 255, 0.05);
      --sidebar-border: rgba(255, 255, 255, 0.1);
      --sidebar-text: rgba(255, 255, 255, 0.9);
      --sidebar-icon: #6c757d;
      --sidebar-icon-active: #0d6efd;
      --sidebar-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --sidebar-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
      --sidebar-highlight: rgba(13, 110, 253, 0.1);
    }

    html,
    body {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background-color: #f8f9fa;
    }

    .sidebar {
      min-height: 100vh;
      height: 100vh;
      max-height: 100vh;
      background: var(--sidebar-bg);
      position: fixed;
      width: var(--sidebar-width);
      z-index: 1000;
      box-shadow: var(--sidebar-shadow);
      overflow-y: auto;
      transition: var(--sidebar-transition);
      border-right: 1px solid var(--sidebar-border);
    }

    .sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background-color: rgba(255, 255, 255, 0.2);
      border-radius: 3px;
    }

    .content {
      margin-left: var(--sidebar-width);
      width: calc(100% - var(--sidebar-width));
      min-height: 100vh;
      background: #f8f9fa;
      transition: var(--sidebar-transition);
    }

    .logo {
      background: transparent !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      padding: 0 !important;
      display: block;
      margin: 0 auto;
      max-width: 130px;
      max-height: 56px;
      filter: drop-shadow(0 0 8px rgba(13, 110, 253, 0.4));
      transition: all 0.3s ease;
    }

    .logo:hover {
      transform: scale(1.05);
      filter: drop-shadow(0 0 12px rgba(13, 110, 253, 0.6));
    }

    .nav-link {
      transition: var(--sidebar-transition);
      border-radius: 6px;
      margin: 4px 0;
      padding: 10px 15px;
      color: var(--sidebar-text);
      position: relative;
      overflow: hidden;
      font-weight: 400;
      display: flex;
      align-items: center;
    }

    .nav-link::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 3px;
      background: var(--primary-color);
      transform: scaleY(0);
      transform-origin: bottom;
      transition: var(--sidebar-transition);
    }

    .nav-link:hover,
    .nav-link:focus {
      background: var(--sidebar-hover);
      color: white;
    }

    .nav-link:hover::before,
    .nav-link:focus::before {
      transform: scaleY(1);
    }

    .nav-link.active {
      background: var(--sidebar-active);
      color: white;
      font-weight: 500;
    }

    .nav-link.active::before {
      transform: scaleY(1);
    }

    .nav-link i {
      color: var(--sidebar-icon);
      margin-right: 12px;
      font-size: 1.1rem;
      transition: var(--sidebar-transition);
      min-width: 24px;
      text-align: center;
    }

    .nav-link:hover i,
    .nav-link:focus i,
    .nav-link.active i {
      color: var(--sidebar-icon-active);
    }

    .sidebar-group .nav-link {
      font-weight: 500;
    }

    .sidebar-group .collapse .nav-link {
      font-weight: 400;
      padding-left: 2.8rem;
      font-size: 0.95em;
      margin-left: 10px;
      border-left: 1px dashed var(--sidebar-border);
      border-radius: 0 6px 6px 0;
    }

    .sidebar-group .collapse .nav-link::before {
      display: none;
    }

    .sidebar .bi-chevron-down {
      transition: var(--sidebar-transition);
      font-size: 0.9rem;
      color: var(--sidebar-icon);
    }

    .sidebar-group .nav-link[aria-expanded="true"] .bi-chevron-down {
      transform: rotate(180deg);
      color: var(--sidebar-icon-active);
    }

    .sidebar-header {
      padding: 5px 5px 5px;
      margin-bottom: 10px;
      border-bottom: 1px solid var(--sidebar-border);
      text-align: center;
    }

    .sidebar-title {
      color: white;
      font-size: 1.1rem;
      margin-top: 10px;
      font-weight: 500;
      letter-spacing: 0.5px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .user-panel {
      background: rgba(0, 0, 0, 0.2);
      padding: 15px;
      border-radius: 8px;
      margin-top: auto;
      border: 1px solid var(--sidebar-border);
      backdrop-filter: blur(5px);
    }

    .user-panel small {
      color: rgba(255, 255, 255, 0.7);
      display: block;
      font-size: 0.75rem;
    }

    .user-panel .username {
      font-weight: 500;
      color: white;
      margin: 5px 0;
    }

    .user-panel .badge {
      font-size: 0.7rem;
      padding: 4px 8px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    .logout-btn {
      background: rgba(220, 53, 69, 0.7);
      border: none;
      transition: var(--sidebar-transition);
    }

    .logout-btn:hover {
      background: rgba(220, 53, 69, 0.9);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    .logout-btn i {
      color: white !important;
    }

    /* Futuristic highlight effect */
    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
      }

      70% {
        box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(13, 253, 216, 0);
      }
    }

    .highlight-pulse {
      animation: pulse 2s infinite;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }

      .content {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>

</head>

<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar text-white p-3 d-flex flex-column">
      <div class="sidebar-header">
        <a href="dashboard.php" class="d-inline-block">
          <img
            src="<?= $base_url ?? '' ?>assets/terralogix1.png"
            alt="Inventory System Logo"
            class="img-fluid logo highlight-pulse"
            style="display: block; margin: 0 auto; max-width: 150px; max-height: 150px;" />
        </a>

      </div>

      <ul class="nav flex-column flex-grow-1">
        <!-- Dashboard -->
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
            href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <!-- Inventory Group -->
        <li class="nav-item sidebar-group">
          <a class="nav-link d-flex justify-content-between align-items-center"
            data-bs-toggle="collapse" href="#invGroup" role="button" aria-expanded="false" aria-controls="invGroup">
            <span>
              <i class="bi bi-box-seam"></i>
              <span>Inventory</span>
            </span>
            <i class="bi bi-chevron-down"></i>
          </a>
          <div class="collapse" id="invGroup">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'items.php' ? 'active' : '' ?>" href="items.php">
              <i class="bi bi-box"></i>
              <span>Items</span>
            </a>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
              <a href="#" class="nav-link" id="openAddItemModal">
                  <i class="bi bi-plus-circle"></i>
                  <span>Add Item</span>
              </a>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'old_stocks.php' ? 'active' : '' ?>" href="old_stocks.php">
                <i class="bi bi-archive"></i>
                <span>Old Stocks</span>
              </a>
            <?php endif; ?>
          </div>
        </li>
        <!-- Transactions Group -->
        <li class="nav-item sidebar-group">
          <a class="nav-link d-flex justify-content-between align-items-center"
            data-bs-toggle="collapse" href="#transGroup" role="button" aria-expanded="false" aria-controls="transGroup">
            <span>
              <i class="bi bi-truck"></i>
              <span>Transactions</span>
            </span>
            <i class="bi bi-chevron-down"></i>
          </a>
          <div class="collapse" id="transGroup">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'delivery_receipt.php' ? 'active' : '' ?>" href="delivery_receipt.php">
              <i class="bi bi-receipt"></i>
              <span>Delivery Receipt</span>
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'delivery_history.php' ? 'active' : '' ?>" href="delivery_history.php">
              <i class="bi bi-clock-history"></i>
              <span>Delivery History</span>
            </a>
          </div>
        </li>
        <!-- Requests Group -->
        <li class="nav-item sidebar-group">
          <a class="nav-link d-flex justify-content-between align-items-center"
            data-bs-toggle="collapse" href="#reqGroup" role="button" aria-expanded="false" aria-controls="reqGroup">
            <span>
              <i class="bi bi-clipboard2-pulse"></i>
              <span>Requests</span>
            </span>
            <i class="bi bi-chevron-down"></i>
          </a>
          <div class="collapse" id="reqGroup">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'materials_request.php' ? 'active' : '' ?>" href="materials_request.php">
              <i class="bi bi-clipboard2"></i>
              <span>Materials Request</span>
            </a>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'materials_request_admin.php' ? 'active' : '' ?>" href="materials_request_admin.php">
                <i class="bi bi-check-circle"></i>
                <span>Approve Requests</span>
              </a>
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'purchase_orders.php' ? 'active' : '' ?>" href="purchase_orders.php">
                <i class="bi bi-clipboard-check"></i>
                <span>Purchase Orders</span>
              </a>
            <?php endif; ?>
          </div>
        </li>
        <!-- Single links (admin only) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="users.php">
              <i class="bi bi-people"></i>
              <span>Manage Users</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'returned_items_page.php' ? 'active' : '' ?>"
              href="returned_items_page.php">
              <i class="bi bi-arrow-return-left"></i>
              <span>Returned Items</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">
              <i class="bi bi-truck"></i>
              <span>Suppliers</span>
            </a>
          </li>
        <?php endif; ?>

      <?php if (in_array($user_role, ['admin', 'manager'])): ?>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tools.php' ? 'active' : '' ?>" href="tools.php">
                <i class="bi bi-wrench-adjustable"></i>
                Tools
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'master_items.php' ? 'active' : '' ?>" href="master_items.php">
                <i class="bi bi-box-seam"></i>
                Master Items
            </a>
        </li>
      <?php endif; ?>
        <li class="nav-item mt-auto">
          <a class="nav-link logout-btn" href="logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>

      <div class="user-panel">
        <small class="text-center d-block">Logged in as</small>
        <div class="username text-center"><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></div>
        <div class="text-center">
          <small class="badge bg-primary animate__animated animate__pulse animate__infinite"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'none')) ?></small>
        </div>
      </div>
    </div>
    <!-- Main Content -->
    <main class="content p-4">
      <?php
      if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
          . htmlspecialchars($_SESSION['success']) .
          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['success']);
      }
      if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
          . htmlspecialchars($_SESSION['error']) .
          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['error']);
      }
      if (!empty($view) && file_exists($view)) {
        if (isset($form_data) && is_array($form_data)) extract($form_data); // <-- THIS IS THE KEY LINE
        include $view;
      } else {
        echo '<div class="alert alert-danger">Page content not found or view variable not set correctly. Missing view: ' . htmlspecialchars($view ?? '[view variable not set]') . '</div>';
        error_log("Layout Error: View file issue. Path: " . ($view ?? '[view variable not set]'));
      }
      ?>
    </main>
  </div>

  <!-- Rest of your HTML remains the same -->
  <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form class="modal-content needs-validation" id="addItemForm" action="add_master_item.php" method="POST" autocomplete="off" novalidate>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addItemModalLabel">Add New Item</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="item_name" class="form-label">Name</label>
              <input type="text" class="form-control" name="name" id="item_name" required>
            </div>
            <div class="col-md-6">
              <label for="item_sku" class="form-label">Serial No.</label>
              <input type="text" class="form-control" name="sku" id="item_sku">
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label for="item_description" class="form-label">Description</label>
            <textarea class="form-control" name="description" id="item_description" rows="2"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="item_unit" class="form-label">Unit</label>
              <select class="form-select" name="unit" id="item_unit" required>
                <option value="" disabled selected>Select unit</option>
                <option value="pcs">Pieces</option>
                <option value="pc">Piece</option>
                <option value="pr">Pair</option>
                <option value="assy">Assembly</option>
                <option value="unt">Unit</option>
                <option value="set">Set</option>
                <option value="mtrs">Meters</option>
                <option value="ft">Feet</option>
                <option value="box">Box</option>
                <option value="pck">Pack</option>
                <option value="roll">Roll</option>
                <option value="kg">Kilogram</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="item_qty" class="form-label">Qty</label>
              <input type="number" class="form-control" name="quantity" id="item_qty" min="0" step="any" value="1" required>
            </div>
          </div>
          <div class="col-md-4">
            <label for="item_category" class="form-label">Category</label>
            <select class="form-select" name="category" id="item_category" required>
            <option value="Structured Cabling">Structured Cabling</option>
            <option value="FDAS">FDAS</option>
            <option value="Network Switches">Network Switches</option>
            <option value="Router/Firewall">Router/Firewall</option>
            <option value="WAP">WAP</option>
            <option value="Camera/CCTV Equipments">Camera/Surveillance Equipment</option>
            <option value="Electrical">Electrical</option>
             <option value="Telephone">Telephone</option>
            <option value="PVC Pipe">PVC Pipe</option>
            <option value="EMT Pipe">EMT Pipe</option>
            <option value="PVC Fittings">PVC Fittings</option>
            <option value="Power Tools">Power Tools</option>
            <option value="Personal Protective Equipment (PPE)">Personal Protective Equipment (PPE)</option>
            <option value="Access Control">Access Control</option>
            <option value="Tools">Tools</option>
            <option value="Consumables">Consumables</option>
            <option value="Copper Cables">Copper Cables</option>
            <option value="Computer Set">Computer Set</option>
            <option value="Printer">Printer</option>
            <option value="Kiosk">Kiosk</option>
            <option value="Data Cabinet and Trays">Data Cabinet and Trays</option>

            </select>
            <input type="text" class="form-control mt-2 d-none" id="item_category_other" name="category_other" placeholder="Specify other category">
          </div>


          <hr>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label for="addUnitPrice" class="form-label">Unit Price (₱)</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" name="unit_price" id="addUnitPrice" step="0.01" min="0.01" required>
              </div>
              <div class="invalid-feedback">Please provide a valid unit price.</div>
            </div>
            <div class="col-md-4">
              <label for="addMinStockLevel" class="form-label">Min. Stock Level</label>
              <input type="number" class="form-control" name="min_stock_level" id="addMinStockLevel" min="0" value="0" required>
              <div class="invalid-feedback">Please provide a valid minimum stock level.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Taxable</label>
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" name="taxable" id="addTaxableSwitch">
                <label class="form-check-label" for="addTaxableSwitch">Apply 12% Tax</label>
              </div>
            </div>
          </div>
          <div class="row g-3 mt-3">
            <div class="col-md-4">
              <label class="form-label">Calc. Non-Taxed Unit Price</label>
              <input class="form-control" type="text" id="addPriceNonTaxedDisplay" readonly value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label">Calc. Taxed Unit Price</label>
              <input class="form-control" type="text" id="addPriceTaxedDisplay" readonly value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label">Calc. Total Value (Taxed)</label>
              <input class="form-control" type="text" id="addTotalPriceDisplay" readonly value="0.00">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Add Item</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function() {
         $('#addItemForm').on('submit', function(e) {
    e.preventDefault(); // Prevent normal form submit

    var form = $(this);
    var formData = form.serialize();
    var submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true);

    $.post('add_master_item.php', formData, function(response) {
      if (response.trim() === 'success') {
        $('#addItemModal').modal('hide');
        $('main.content').prepend(`
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            Item added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `);
      } else {
        $('main.content').prepend(`
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Error: ` + response + `
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        `);
      }
    }).fail(function(xhr, status, error) {
      $('main.content').prepend(`
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          Error: ${xhr.status} - ${xhr.statusText}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `);
    }).always(function() {
      submitBtn.prop('disabled', false);
    });
  });
      // Only initialize DataTables if not already done
      if (!$.fn.DataTable.isDataTable('#returnedItemsTable')) {
        $('#returnedItemsTable').DataTable({
          responsive: true,
          order: [
            [1, 'desc']
          ]
        });
      }
      // --- Show modal when sidebar "Add Item" is clicked ---
        $('#openAddItemModal').on('click', function(e) {
          e.preventDefault();
           $('#addItemModal').modal('show');
        });

      // Form validation for Bootstrap 5
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
// Calculation logic for Add Item Modal
function recalcAddItemPrices() {
    let price = parseFloat($('#addUnitPrice').val()) || 0;
    let qty = parseFloat($('#item_qty').val()) || 0;
    let taxable = $('#addTaxableSwitch').is(':checked');
    let nonTaxedPrice = price;
    let taxedPrice = taxable ? (price * 1.12) : price;
    let totalTaxed = taxedPrice * qty;

    // Round to 2 decimal places
    $('#addPriceNonTaxedDisplay').val(nonTaxedPrice.toFixed(2));
    $('#addPriceTaxedDisplay').val(taxedPrice.toFixed(2));
    $('#addTotalPriceDisplay').val(totalTaxed.toFixed(2));
}

// Update calculation on any relevant input change
$('#addUnitPrice, #item_qty').on('input', recalcAddItemPrices);
$('#addTaxableSwitch').on('change', recalcAddItemPrices);

// Optionally recalc on modal show
$('#addItemModal').on('shown.bs.modal', recalcAddItemPrices);

      // Auto-fill item snapshot fields on item select (for the hidden fields)
      $('#addItemId').on('change', function() {
        const opt = $(this).find('option:selected');
        $('#addNameAtReturn').val(opt.data('name'));
        $('#addSkuAtReturn').val(opt.data('sku'));
        $('#addDescriptionAtReturn').val(opt.data('description'));
        $('#addUnitAtReturn').val(opt.data('unit'));
        $('#addUnitPriceAtReturn').val(opt.data('unit_price'));
        $('#addPriceTaxedAtReturn').val(opt.data('price_taxed'));
        $('#addPriceNontaxedAtReturn').val(opt.data('price_nontaxed'));
      });

      // Edit Return Modal logic (populate modal with AJAX)
      $('.edit-return-btn').on('click', function() {
        const returnId = $(this).data('id');
        $('#editReturnForm').trigger("reset").removeClass('was-validated');

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

      // Utility to escape HTML
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
</body>

</html>
<?php ob_end_flush(); ?>