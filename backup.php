<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php'; // Only admins can use
date_default_timezone_set('Asia/Manila'); // Set to your timezone

$backup_file = 'backup_' . date('Ymd_His') . '.sql';
$command = "mysqldump --user=YOUR_DB_USER --password=YOUR_DB_PASSWORD --host=localhost YOUR_DB_NAME > $backup_file";
system($command, $output);

if (file_exists($backup_file)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($backup_file));
    readfile($backup_file);
    unlink($backup_file); // Remove file after download for security
    exit;
} else {
    echo "<div class='alert alert-danger'>Backup failed. Check your mysqldump settings.</div>";
}
?>
