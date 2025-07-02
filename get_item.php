<?php
require_once __DIR__ . '/includes/config.php'; // Adjust path as needed
require_once __DIR__ . '/includes/auth.php';   // Adjust path as needed

// Clear output buffer if anything was outputted by includes (best if includes don't output)
if (ob_get_level() > 0) ob_clean(); 
header('Content-Type: application/json');

// Verify session and role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate item ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || (int)$_GET['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing item ID.']);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Prepare and execute query with prepared statement
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        $stmt->close();
        exit;
    }

    // Return item data
    $item = $result->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'success' => true,
        'data' => $item
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_item.php: " . $e->getMessage()); // Log the actual error
    echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred. Please try again later.' // Generic message to user
        // 'debug_message' => $e->getMessage() // Optionally include for dev environments
    ]);
}
exit;
?>