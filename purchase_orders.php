<?php
// inventory-system/purchase_orders.php (Controller)

// Ensure config is loaded first for constants like VIEWS_PATH
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
// require_once __DIR__ . '/includes/is_admin.php'; // Uncomment if access should be restricted to admins
require_once __DIR__ . '/includes/db.php';

$status_filter = $_GET['status'] ?? 'all';
$supplier_filter = $_GET['supplier_id'] ?? 'all';
$search_term = trim($_GET['search'] ?? '');

$params = [];
$types = '';
$query = "SELECT 
    po.*, 
    s.name as supplier_name, 
    u.username as created_by_username, 
    mr.id as material_request_id_display,
    IFNULL(SUM(poi.quantity * IF(poi.taxable=1, poi.unit_price*1.12, poi.unit_price)), 0) as total_est
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.id
JOIN users u ON po.created_by = u.id
LEFT JOIN materials_requests mr ON po.request_id = mr.id
LEFT JOIN purchase_order_items poi ON poi.order_id = po.id
WHERE 1=1";

// ----- All filters must come BEFORE GROUP BY/ORDER BY -----
if ($status_filter !== 'all') {
    $query .= " AND po.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($supplier_filter !== 'all' && is_numeric($supplier_filter)) {
    $query .= " AND po.supplier_id = ?";
    $params[] = (int)$supplier_filter;
    $types .= 'i';
}
if ($search_term !== '') {
    $query .= " AND (po.sales_invoice_no LIKE ? OR po.notes LIKE ? OR s.name LIKE ?)";
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term;
    $types .= 's';
    $params[] = $like_term;
    $types .= 's';
    $params[] = $like_term;
    $types .= 's';
}
// ----- Only now, add GROUP BY and ORDER BY! -----
$query .= " GROUP BY po.id ORDER BY po.order_date DESC, po.id DESC";

// Debug SQL? Uncomment the next line for troubleshooting
// error_log($query);

// Prepare and execute
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Error preparing PO list statement: " . $conn->error . " | SQL: " . $query);
    die("A database error occurred while fetching purchase orders. Please check server logs or contact support.");
}

if (!empty($params)) {
    if (!$stmt->bind_param($types, ...$params)) {
        error_log("Error binding PO list parameters: " . $stmt->error);
        die("A database error occurred (bind params). Please check server logs or contact support.");
    }
}

if (!$stmt->execute()) {
    error_log("Error executing PO list statement: " . $stmt->error);
    die("A database error occurred (execute). Please check server logs or contact support.");
}

$orders = $stmt->get_result(); // This is the $orders mysqli_result object for the view
$stmt->close();

// Fetch suppliers for filter dropdown
$suppliers_result_for_view = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers_list_for_view = []; // Initialize as empty array
if ($suppliers_result_for_view) {
    $suppliers_list_for_view = $suppliers_result_for_view->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Failed to fetch suppliers for PO filter: " . $conn->error);
}

$page_title = "Purchase Orders"; // $page_title will be used by layout.php

// Ensure VIEWS_PATH is defined and points to your views directory correctly
// It should be defined in config.php, e.g., define('VIEWS_PATH', __DIR__ . '/../views/');
$view_file_path = VIEWS_PATH . 'purchase_orders.php'; // This is the path to the view file

if (!file_exists($view_file_path)) {
    error_log("View file missing for purchase_orders page: " . $view_file_path);
    die("Critical error: Page content cannot be loaded. Please contact support.");
}
$view = $view_file_path; // $view will be used by layout.php

include __DIR__ . '/templates/layout.php';
?>
