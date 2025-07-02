<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/is_admin.php';
require_once __DIR__ . '/includes/db.php';

// Get all users
$users = mysqli_query($conn, "SELECT id, username, email, role FROM users ORDER BY username");

$view = 'views/users.php';
include 'templates/layout.php';