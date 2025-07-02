<?php
// $items is already passed by the controller.
$columns = [];
if (!empty($items)) {
    $columns = array_keys($items[0]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Master Items</h1>
    <div class="card p-3">
        <table id="masterItemsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <td>
                                    <?php
                                        // Format 'taxable' if column exists
                                        if ($col == 'taxable') {
                                            echo $item[$col] ? 'Yes' : 'No';
                                        } else {
                                            echo htmlspecialchars($item[$col]);
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= count($columns) ?>" class="text-center">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#masterItemsTable').DataTable();
    });
</script>
</body>
</html>
