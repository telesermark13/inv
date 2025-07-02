<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';   
$user_role = $_SESSION['user']['role'] ?? '';
if (!in_array($user_role, ['admin', 'manager'])) {
    header('Location: index.php');
    exit;
}
$sql = "SELECT t.*, u.username AS assigned_person_name
        FROM tools t
        LEFT JOIN users u ON t.assigned_person = u.id
        ORDER BY t.name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tools Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS and Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f9fafb; }
        .table thead th { background: #f1f3f6; }
        .page-title { display: flex; align-items: center; gap: 0.6rem; }
        .btn { min-width: 80px; }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="page-title h3 mb-0">
            <i class="bi bi-wrench-adjustable"></i> Tools
        </span>
        <a href="tool_add.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Tool
        </a>
    </div>
    <div class="card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered table-striped m-0 align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Size</th>
                    <th>Description</th>
                    <th>Assigned Person</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= (int)$row['quantity'] ?></td>
                            <td><?= htmlspecialchars($row['size']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= htmlspecialchars($row['assigned_person_name'] ?? '—') ?></td>
                            <td>
                                <?php
                                $badge = [
                                    'good' => 'success',
                                    'damaged' => 'danger',
                                    'under repair' => 'warning',
                                    'missing' => 'secondary',
                                    'retired' => 'dark'
                                ][$row['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucwords($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="tool_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="tool_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this tool?')"
                                   title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No tools found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
