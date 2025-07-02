<?php
// inventory-system/purchase_order_action.php
require_once __DIR__ . '/includes/auth.php';
// require_once __DIR__ . '/includes/is_admin.php'; // Or specific role for purchasing actions
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ }
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { /* ... */ }
if (!isset($_POST['po_id']) || !filter_var($_POST['po_id'], FILTER_VALIDATE_INT) || !isset($_POST['action'])) { /* ... */ }

$po_id = (int)$_POST['po_id'];
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

$conn->begin_transaction();
try {
    $stmt_po_details = $conn->prepare("SELECT status, request_id FROM purchase_orders WHERE id = ?");
    if(!$stmt_po_details) throw new Exception("Prepare select PO failed: ".$conn->error);
    $stmt_po_details->bind_param("i", $po_id);
    $stmt_po_details->execute();
    $po_details_res = $stmt_po_details->get_result();
    if($po_details_res->num_rows === 0) throw new Exception("PO #{$po_id} not found.");
    $po_current_data = $po_details_res->fetch_assoc();
    $stmt_po_details->close();

    $new_status = $po_current_data['status']; // Default to current status

    if ($action === 'mark_as_purchased') { // This is "Ordered" in our refined enum
        if (!in_array($po_current_data['status'], ['pending', 'pending_po_approval', 'approved_to_order'])) {
            throw new Exception("PO #{$po_id} cannot be marked as purchased from its current state: " . $po_current_data['status']);
        }
        $new_status = 'ordered'; // Or 'purchased' if you add that to your enum
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare update PO status (purchased) failed: " . $conn->error);
        $stmt->bind_param("si", $new_status, $po_id);
        if (!$stmt->execute()) throw new Exception("Execute update PO status (purchased) failed: " . $stmt->error);
        $stmt->close();
        $_SESSION['success'] = "PO #{$po_id} marked as '{$new_status}'.";

    } elseif ($action === 'cancel_po') {
        if (in_array($po_current_data['status'], ['fully_received', 'cancelled'])) {
             throw new Exception("PO #{$po_id} is already {$po_current_data['status']} and cannot be cancelled.");
        }
        $new_status = 'cancelled';
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare update PO status (cancel) failed: " . $conn->error);
        $stmt->bind_param("si", $new_status, $po_id);
        if (!$stmt->execute()) throw new Exception("Execute update PO status (cancel) failed: " . $stmt->error);
        $stmt->close();

        // Optional: Update related Material Request status
        if ($po_current_data['request_id']) {
            $mr_id_to_update = $po_current_data['request_id'];
            // Change this status to whatever makes sense in your workflow, e.g., 'closed_po_cancelled'
            // or 'pending_resourcing' if it needs to be looked at again.
            $new_mr_status_after_po_cancel = 'closed_po_cancelled';
            $stmt_update_mr = $conn->prepare("UPDATE materials_requests SET status = ? WHERE id = ? AND status NOT IN ('completed', 'denied')");
            if($stmt_update_mr){
                $stmt_update_mr->bind_param("si", $new_mr_status_after_po_cancel, $mr_id_to_update);
                $stmt_update_mr->execute();
                $stmt_update_mr->close();
            }
        }
        $_SESSION['success'] = "PO #{$po_id} has been cancelled.";

    } elseif ($action === 'update_po_notes') {
        if (!isset($_POST['po_notes'])) throw new Exception("Notes not provided for update.");
        $po_notes = trim($_POST['po_notes']);
        $stmt = $conn->prepare("UPDATE purchase_orders SET notes = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare update PO notes failed: " . $conn->error);
        $stmt->bind_param("si", $po_notes, $po_id);
        if (!$stmt->execute()) throw new Exception("Execute update PO notes failed: " . $stmt->error);
        $stmt->close();
        $_SESSION['success'] = "Notes for PO #{$po_id} updated.";
    }
    // Add 'approve_po_to_order' from previous response if you have that intermediate step
    else if ($action === 'approve_po_to_order') {
        if ($po_current_data['status'] !== 'pending_po_approval') {
            throw new Exception("PO is not in 'Pending Approval' state.");
        }
        $new_status = 'approved_to_order';
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare update PO status failed: " . $conn->error);
        $stmt->bind_param("si", $new_status, $po_id);
        if (!$stmt->execute()) throw new Exception("Execute update PO status failed: " . $stmt->error);
        $stmt->close();
        $_SESSION['success'] = "Purchase Order #{$po_id} approved for ordering.";
    }
    else {
        throw new Exception("Invalid action: " . htmlspecialchars($action));
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("PO Action Error for PO #{$po_id}: " . $e->getMessage());
    $_SESSION['error'] = "Error: " . $e->getMessage();
}
header("Location: purchase_orders.php");
exit;
?>