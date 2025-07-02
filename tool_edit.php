<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (!in_array($_SESSION['user']['role'], ['admin', 'manager'])) {
    header('Location: index.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: tools.php'); exit; }
$stmt = $conn->prepare("SELECT * FROM tools WHERE id=?");
$stmt->bind_param('i', $id); $stmt->execute();
$tool = $stmt->get_result()->fetch_assoc();
if (!$tool) { header('Location: tools.php'); exit; }
$users = $conn->query("SELECT id, username FROM users ORDER BY username");
$name = $tool['name'];
$quantity = $tool['quantity'];
$size = $tool['size'];
$description = $tool['description'];
$assigned_person = $tool['assigned_person'];
$status = $tool['status'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $quantity = max(0, (int)$_POST['quantity']);
    $size = trim($_POST['size']);
    $description = trim($_POST['description']);
    $assigned_person = $_POST['assigned_person'] !== '' ? (int)$_POST['assigned_person'] : null;
    $status = $_POST['status'] ?? 'good';
    if ($name === '') $errors[] = "Tool name is required.";
    if ($size === '') $errors[] = "Tool size is required.";
    if (!in_array($status, ['good','damaged','under repair','missing','retired'])) $errors[] = "Invalid status.";
    if (!$errors) {
        $stmt = $conn->prepare("UPDATE tools SET name=?, quantity=?, size=?, description=?, assigned_person=?, status=? WHERE id=?");
        $stmt->bind_param('sissisi', $name, $quantity, $size, $description, $assigned_person, $status, $id);
        $stmt->execute();
        header('Location: tools.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-wrench-adjustable"></i> Edit Tool</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <form method="post" class="card card-body rounded-4 shadow-sm" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Tool Name</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" required min="0" value="<?= htmlspecialchars($quantity) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Size</label>
            <input type="text" name="size" class="form-control" required value="<?= htmlspecialchars($size) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($description) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Assigned Person</label>
            <select name="assigned_person" class="form-select">
                <option value="">— None —</option>
                <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $assigned_person == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="good" <?= $status=='good'?'selected':'' ?>>Good</option>
                <option value="damaged" <?= $status=='damaged'?'selected':'' ?>>Damaged</option>
                <option value="under repair" <?= $status=='under repair'?'selected':'' ?>>Under Repair</option>
                <option value="missing" <?= $status=='missing'?'selected':'' ?>>Missing</option>
                <option value="retired" <?= $status=='retired'?'selected':'' ?>>Retired</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Save</button>
            <a href="tools.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
