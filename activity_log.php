<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch recent activities (adjust as needed)
$recent_activity = [];
$res = $conn->query("SELECT * FROM inventory_movements ORDER BY created_at DESC LIMIT 100");
while ($row = $res->fetch_assoc()) {
    $recent_activity[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Log</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h2>Recent Activity</h2>
    <a href="dashboard.php">Back to Dashboard</a>
    <ul>
    <?php foreach ($recent_activity as $activity): ?>
        <li>
            <?= htmlspecialchars($activity['user'] ?? 'Unknown') ?>
            <?= ($activity['movement_type'] === 'in') ? 'added' : 'removed' ?>
            <?= $activity['quantity'] ?>
            <?= htmlspecialchars($activity['item_name'] ?? '') ?>
            <small><?= date('M j, g:i a', strtotime($activity['created_at'])) ?></small>
        </li>
    <?php endforeach; ?>
    </ul>
</body>
</html>
