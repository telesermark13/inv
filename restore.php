<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file']['tmp_name'];
    $command = "mysql --user=YOUR_DB_USER --password=YOUR_DB_PASSWORD --host=localhost YOUR_DB_NAME < $file";
    system($command, $output);
    echo "<div class='alert alert-success'>Restore complete. Please check your data.</div>";
}
?>
<form method="post" enctype="multipart/form-data">
    <label>Upload SQL backup to restore:</label>
    <input type="file" name="sql_file" accept=".sql" required>
    <button type="submit" class="btn btn-danger mt-2">Restore Database</button>
</form>
