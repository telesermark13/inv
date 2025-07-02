<?php
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: dashboard.php');
    exit;
}